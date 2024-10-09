<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Jobs;

use App\Models\Sep06Transaction;
use App\Stellar\Sep06Transfer\Sep06Helper;
use App\Stellar\Shared\SepHelper;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use ArgoNavis\PhpAnchorSdk\Stellar\TrustlinesHelper;
use ArgoNavis\PhpAnchorSdk\util\MemoHelper;
use DateTime;
use DateTimeInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;

/**
 * This Job handles SEP-6 DEPOSIT and DEPOSIT_EXCHANGE transactions, that have the status
 * PENDING_USER_TRANSFER_START. Because the server cannot receive real fiat payments,
 * we pretend that we have received the corresponding fiat payment for the deposit
 * and send the user the corresponding Stellar anchor asset funds.
 */
class Sep6DepositPaymentsWatcher implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $horizonUrl;
    private string $networkPassphrase;

    /**
     * The number of times the job may be attempted.
     * 0 = indefinitely
     * @var int
     */
    public $tries = 0;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 0;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->horizonUrl = config('stellar.app.horizon_url');
        $this->networkPassphrase = config('stellar.app.network_passphrase');
        TrustlinesHelper::setLogger(Log::getLogger());
    }

    /**
     * Execute the job.
     */
    public function handle(): void {
        try {
            // select all transactions that wait for user payment.
            $sep6DepositTxs = Sep06Transaction::where(
                'status',
                '=',
                Sep06TransactionStatus::PENDING_USER_TRANSFER_START,
            )->whereIn('kind', [Sep06Helper::KIND_DEPOSIT, Sep06Helper::KIND_DEPOSIT_EXCHANGE])
                ->get();

            if ($sep6DepositTxs === null || count($sep6DepositTxs) === 0) {
                // no waiting transactions found.
                return;
            }

            // in this demo we have no real fiat payments,
            // so we consider all transactions as paid and send the user the purchased
            // Stellar asset amount
            foreach($sep6DepositTxs as $sep6DepositTx) {
                // the Stellar asset should be sent to this user account
                $receiverAccountId = $sep6DepositTx->to_account;

                // asset code and issuer identifying the Stellar asset to be sent
                $assetCode = $sep6DepositTx->request_asset_code;
                $assetIssuer = $sep6DepositTx->request_asset_issuer;

                if ($receiverAccountId === null || $assetCode === null || $assetIssuer === null) {
                    // incomplete data.
                    $sep6DepositTx->status = Sep06TransactionStatus::INCOMPLETE;
                    $sep6DepositTx->save();
                    $this->maybeMakeCallback($sep6DepositTx->id);
                    continue;
                }

                // if the user account does not exist on the Stellar Network, we fund it
                try {
                    $stellarSDK = new StellarSDK($this->horizonUrl);
                    if (!$stellarSDK->accountExists(accountId: $receiverAccountId) &&
                        !$this->fundReceiverAccount($receiverAccountId)) {
                        // if funding did not work, we try it next time the job is running.
                        continue;
                    }
                } catch (HorizonRequestException $e) {
                    $context = ['Job:Sep6DepositWatcher', 'handleDepositPayments'];
                    Log::error(
                        message: 'Could not check if receiver account exists ' . $receiverAccountId. PHP_EOL,
                        context: $context,
                    );
                    SepHelper::logHorizonRequestException($e, $context);
                }

                // check if the receiver account trusts the asset to receive.
                $hasTrustline = TrustlinesHelper::checkIfAccountTrustsAsset(
                    horizonUrl: $this->horizonUrl,
                    accountId: $receiverAccountId,
                    assetCode: $assetCode,
                    assetIssuer: $assetIssuer,
                );

                // check if the users client supports claimable balances.
                // if supported, and the receiver account does not trust the asset to receive
                // we can send a claimable balance
                $claimableBalanceSupported = $sep6DepositTx->claimable_balance_supported ?? false;

                // if no trust and no claimable balances supported we need to set the transaction status
                // to pending trust and wait for the user to establish a trustline for the asset to receive
                if(!$hasTrustline && !$claimableBalanceSupported) {
                    $sep6DepositTx->status = Sep06TransactionStatus::PENDING_TRUST;
                    $sep6DepositTx->save();
                    $this->maybeMakeCallback($sep6DepositTx->id);
                    continue;
                }

                // amount of the asset to send
                $amount = $sep6DepositTx->amount_out ?? $sep6DepositTx->amount_expected;
                if ($amount === null) {
                    // incomplete data.
                    // in this demo we do not know how much fiat has been received,
                    // otherwise (in a real business logic) we could calculate the amount depending on the
                    // received fiat.
                    $sep6DepositTx->status = Sep06TransactionStatus::INCOMPLETE;
                    $sep6DepositTx->save();
                    $this->maybeMakeCallback($sep6DepositTx->id);
                    continue;
                }

                // check if the user wanted a memo to be attached to the payment.
                $memo = Memo::none();
                if ($sep6DepositTx->memo !== null && $sep6DepositTx->memo_type !== null) {
                    $memo = MemoHelper::makeMemoFromSepRequestData($sep6DepositTx->memo, $sep6DepositTx->memo_type);
                }

                // send the Stellar payment or send claimable balance
                $stellarTransactionId = null;
                $claimableBalanceId = null;
                if ($hasTrustline) {
                    // receiver account trusts our asset => we can send the payment
                    $stellarTransactionId = $this->sendDepositPayment(
                        receiverAccountId: $receiverAccountId,
                        assetCode: $assetCode,
                        assetIssuer: $assetIssuer,
                        amount: strval($amount),
                        memo: $memo,
                    );
                } elseif($claimableBalanceSupported) {
                    // receiver account does not trust our asset yet, but claimable balances are supported
                    // => we can send a claimable balance
                    $claimableBalanceResult = $this->sendDepositClaimableBalance(
                        receiverAccountId: $receiverAccountId,
                        assetCode: $assetCode,
                        assetIssuer: $assetIssuer,
                        amount: strval($amount),
                        memo: $memo,
                    );
                    if ($claimableBalanceResult !== null &&
                        array_key_exists('stellarTransactionId', $claimableBalanceResult)) {
                        $stellarTransactionId = $claimableBalanceResult['stellarTransactionId'];
                        if (array_key_exists('claimableBalanceId', $claimableBalanceResult)) {
                            $claimableBalanceId = $claimableBalanceResult['claimableBalanceId'];
                        }
                    }
                }
                // update the transaction
                if ($stellarTransactionId !== null) {

                    $sep6DepositTx->stellar_transaction_id = $stellarTransactionId;
                    $sep6DepositTx->claimable_balance_id = $claimableBalanceId;

                    $sep6DepositTx->amount_out = $amount;
                    $sep6DepositTx->to_account = $receiverAccountId;
                    $distributionAccountKp = $this->getDistributionAccountKeyPairForAsset($assetCode, $assetIssuer);
                    $fromAccount = $distributionAccountKp?->getAccountId();
                    $sep6DepositTx->from_account = $fromAccount;

                    $sep6DepositTx->status = Sep06TransactionStatus::COMPLETED;
                    $sep6DepositTx->tx_completed_at = (new DateTime())->format(DateTimeInterface::ATOM);

                    $sep6DepositTx->save();
                    $this->maybeMakeCallback($sep6DepositTx->id);
                }
            }
        } catch (HorizonRequestException $e) {
            SepHelper::logHorizonRequestException($e, context: ['Job:Sep6DepositsWatcher']);
        } catch (Exception $e) {
            Log::error(message: $e->getTraceAsString() . PHP_EOL, context: ['Job:Sep6DepositsWatcher']);
        }
    }

    /**
     * Funds a given receiver account on the stellar network by using the server account.
     *
     * @param string $receiverAccountId the id of the account to be funded.
     * @return bool true if successfully funded.
     */
    private function fundReceiverAccount(string $receiverAccountId) : bool {
        // server account id and secret key used to sign the payment
        $serverAccountId = config('stellar.server.server_account_id');
        $serverSigningKey = config('stellar.server.server_account_signing_key');

        try {
            $serverKeyPair = KeyPair::fromSeed($serverSigningKey);

            // SEP-6 states:
            // If the given Stellar account does not exist, on receipt of the deposit, the anchor should use the
            // CreateAccount operation to create the account with at least enough XLM for the minimum reserve and
            // a trust line to the requested asset (2.01 XLM is recommended).
            $createAccountOperation = (new CreateAccountOperationBuilder(
                destination: $receiverAccountId,
                startingBalance: '2.01',
            ))->build();

            $stellarSDK = new StellarSDK($this->horizonUrl);
            $network = new Network($this->networkPassphrase);

            $sourceAccount = $stellarSDK->requestAccount(accountId: $serverAccountId);

            $tx = (new TransactionBuilder($sourceAccount))
                ->setMaxOperationFee(1000)
                ->addOperation($createAccountOperation)
                ->build();

            $tx->sign($serverKeyPair, $network);

            $response = $stellarSDK->submitTransaction($tx);

            return $response->isSuccessful();
        } catch (HorizonRequestException $e) {
            SepHelper::logHorizonRequestException($e, context: ['Job:Sep6DepositsWatcher', 'fundReceiverAccount']);
        } catch (Exception $e) {
            Log::error(
                message: $e->getTraceAsString() . PHP_EOL,
                context: ['Job:Sep6DepositsWatcher', 'fundReceiverAccount'],
            );
        }
        return false;
    }

    /**
     * Finds the distribution account data for the given anchor asset and returns it's signing keypair.
     * @param string $assetCode the asset code of the anchor asset to find the distribution account for.
     * @param string $assetIssuer the asset issuer of the anchor asset to find the distribution account for.
     * @return KeyPair|null the signing keypair of the distribution account if found.
     */
    private function getDistributionAccountKeyPairForAsset(string $assetCode, string $assetIssuer) : ?KeyPair {
        if ($assetCode === config('stellar.assets.usdc_asset_code') &&
            $assetIssuer == config('stellar.assets.usdc_asset_issuer_id')) {
            $distributionAccountSigningKey = config('stellar.assets.usdc_asset_distribution_signing_key');
            return KeyPair::fromSeed($distributionAccountSigningKey);
        } else if ($assetCode === config('stellar.assets.jpyc_asset_code') &&
            $assetIssuer == config('stellar.assets.jpyc_asset_issuer_id')) {
            $distributionAccountSigningKey = config('stellar.assets.jpyc_asset_distribution_signing_key');
            return KeyPair::fromSeed($distributionAccountSigningKey);
        }
        Log::error(
            message: 'Distribution account not found for ' . $assetCode . ':' . $assetIssuer . PHP_EOL,
            context: ['Job:Sep6DepositsWatcher', 'getDistributionAccountKeyPairForAsset'],
        );
        return null;
    }

    /**
     * Sends a stellar payment for the given anchor asset to the receiver account using the assets distribution account.
     *
     * @param string $receiverAccountId the id of the account that should receive the payment.
     * @param string $assetCode asset code of the anchor asset to be sent.
     * @param string $assetIssuer asset issuer of the anchor asset to be sent.
     * @param string $amount the amount to be sent.
     * @param Memo|null $memo memo to add to the transaction.
     * @return string|null returns the stellar transaction id if the payment was successful. otherwise null.
     */
    private function sendDepositPayment(
        string $receiverAccountId,
        string $assetCode,
        string $assetIssuer,
        string $amount,
        ?Memo $memo,
    ) : ?string {

        // find the distribution account for the anchor asset to be sent and load it's secret key, so that
        // we can sign the transaction.
        $distributionAccountKeyPair = $this->getDistributionAccountKeyPairForAsset($assetCode, $assetIssuer);
        if ($distributionAccountKeyPair === null) {
            Log::error(message: 'could not find distribution account for ' . $assetCode . ':' . $assetIssuer . PHP_EOL,
                context: ['Job:Sep6DepositsWatcher', 'sendDepositPayment']);
            return null;
        }
        try {
            $distributionAccountId = $distributionAccountKeyPair->getAccountId();

            $stellarSDK = new StellarSDK($this->horizonUrl);
            $network = new Network($this->networkPassphrase);

            // load the account data from stellar (needed for sequence number)
            $sourceAccount = $stellarSDK->requestAccount(accountId: $distributionAccountId);

            $asset = Asset::createNonNativeAsset($assetCode, $assetIssuer);

            $paymentOperation = (new PaymentOperationBuilder(
                destinationAccountId: $receiverAccountId,
                asset: $asset,
                amount: $amount,
            ))->build();

            $txBuilder = (new TransactionBuilder($sourceAccount))
                ->setMaxOperationFee(1000)
                ->addOperation($paymentOperation);

            if ($memo != null) {
                $txBuilder->addMemo($memo);
            }

            $tx = $txBuilder->build();
            $tx->sign($distributionAccountKeyPair, $network);
            $response = $stellarSDK->submitTransaction($tx);

            if ($response->isSuccessful()) {
                return $response->getHash();
            }
        } catch (HorizonRequestException $e) {
            SepHelper::logHorizonRequestException($e, context: ['Job:Sep6DepositsWatcher', 'sendDepositPayment']);
        } catch (Exception $e) {
            Log::error(
                message: $e->getTraceAsString() . PHP_EOL,
                context: ['Job:Sep6DepositsWatcher', 'sendDepositPayment'],
            );
        }
        return null;
    }

    /**
     * Sends a claimable balance for the given anchor asset to the receiver account using the assets distribution
     * account.
     * @param string $receiverAccountId the id of the account to receive the claimable balance.
     * @param string $assetCode asset code of the anchor asset to be sent.
     * @param string $assetIssuer asset issuer of the anchor asset to be sent.
     * @param string $amount amount to be sent.
     * @param Memo|null $memo memo to be added to the transaction.
     * @return array<string,string>|null returns the stellar transaction id and the claimable balance id if successful
     * ['stellarTransactionId' => '...', 'claimableBalanceId' => '...']. Otherwise, null.
     */
    private function sendDepositClaimableBalance(
        string $receiverAccountId,
        string $assetCode,
        string $assetIssuer,
        string $amount,
        ?Memo $memo,
    ) : ?array {
        // find the distribution account for the anchor asset to be sent and load it's secret key, so that
        // we can sign the transaction.
        $distributionAccountKeyPair = $this->getDistributionAccountKeyPairForAsset($assetCode, $assetIssuer);
        if ($distributionAccountKeyPair === null) {
            Log::error(message: 'could not find distribution account for ' . $assetCode . ':' . $assetIssuer . PHP_EOL,
                context: ['Job:Sep6DepositsWatcher', 'sendDepositClaimableBalance']);
            return null;
        }

        try {
            $stellarSDK = new StellarSDK($this->horizonUrl);
            $network = new Network($this->networkPassphrase);

            $distributionAccountId = $distributionAccountKeyPair->getAccountId();
            $sourceAccount = $stellarSDK->requestAccount(accountId: $distributionAccountId);
            $asset = Asset::createNonNativeAsset($assetCode, $assetIssuer);

            // in this demo we set only the receiver account as a claimant with the predicate
            // unconditional. In a real business logic you could use other variations, such as
            // setting a time limit and setting the distribution account as a claimant so that
            // the funds can be recovered if the receiver does not claim them in time.
            $claimant = new Claimant(
                destination:$receiverAccountId,
                predicate: new XdrClaimPredicate(
                    new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL),
                ),
            );

            $createClaimableBalanceOp = (new CreateClaimableBalanceOperationBuilder(
                claimants: [$claimant],
                asset: $asset,
                amount: $amount,
            ))->build();

            $txBuilder = (new TransactionBuilder($sourceAccount))
                ->setMaxOperationFee(1000)
                ->addOperation($createClaimableBalanceOp);
            if ($memo != null) {
                $txBuilder->addMemo($memo);
            }
            $tx = $txBuilder->build();
            $tx->sign($distributionAccountKeyPair, $network);
            $response = $stellarSDK->submitTransaction($tx);

            if ($response->isSuccessful()) {
                $stellarTransactionId = $response->getHash();
                /**
                 * @var array<string,string> $result
                 */
                $result = ['stellarTransactionId' => $stellarTransactionId];

                // extract the claimable balance id from the transaction result
                $txResultXdr = $response->getResultXdr();
                $txResults = $txResultXdr->getResult()->getResults();
                if ($txResults !== null && count($txResults) > 0) {
                    $createClaimableBalanceResult = $txResults[0]->getResultTr()?->getCreateClaimableBalanceResult();
                    if ($createClaimableBalanceResult?->balanceID !== null) {
                        $hex = $createClaimableBalanceResult->balanceID->getHash();
                        // left padded with 0, so it can be used in successive horizon requests.
                        $result += ['claimableBalanceId' =>
                            str_pad($hex, 72, '0', STR_PAD_LEFT)];
                    }
                }
                return $result;
            }
        } catch (HorizonRequestException $e) {
            SepHelper::logHorizonRequestException($e,
                context: ['Job:Sep6DepositsWatcher', 'sendDepositClaimableBalance']);
        } catch (Exception $e) {
            Log::error(
                message: $e->getTraceAsString() . PHP_EOL,
                context: ['Job:Sep6DepositsWatcher', 'sendDepositClaimableBalance'],
            );
        }
        return null;
    }

    private function maybeMakeCallback(string $sep6TransactionId) : void {

    }
}
