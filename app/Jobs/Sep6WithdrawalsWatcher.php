<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Jobs;

use App\Models\AnchorAsset;
use App\Models\Sep06Transaction;
use App\Models\Sep38Rate;
use App\Stellar\Sep06Transfer\Sep06Helper;
use App\Stellar\Sep38Quote\Sep38Helper;
use App\Stellar\Shared\SepHelper;
use ArgoNavis\PhpAnchorSdk\exception\AccountNotFound;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSepRequest;
use ArgoNavis\PhpAnchorSdk\exception\QuoteNotFoundForId;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\Sep38Quote;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefundPayment;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefunds;
use ArgoNavis\PhpAnchorSdk\Stellar\PaymentsHelper;
use ArgoNavis\PhpAnchorSdk\Stellar\ReceivedPayment;
use ArgoNavis\PhpAnchorSdk\Stellar\ReceivedPaymentsQueryResult;
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
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

use function json_encode;

/**
 * This Job checks if the anchor received payments for SEP-6 WITHDRAW and WITHDRAW_EXCHANGE transactions
 * that have the status PENDING_USER_TRANSFER_START (waiting for the users stellar anchor asset payment to the anchor).
 * If the Stellar payment has been received, the job updates the transaction to have the status 'COMPLETED'.
 * This is for demo purposes on how to use the php anchor sdk to check for incoming payments and how they could be
 * handled. It does not send real fiat payments to the users bank account like a real business logic should do.
 */
class Sep6WithdrawalsWatcher implements ShouldQueue, ShouldBeUnique
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
    public $timeout = 300;

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
        PaymentsHelper::setLogger(Log::getLogger());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // load anchor receiving accounts
            $receiverAccountsIds = $this->getReceiverAnchorAccountsIds();

            // we will need this for significant decimals.
            $anchorAssets = $this->getAnchorAssets();

            // for each found receiving account (distribution account), find the transactions
            // with status 'PENDING_USER_TRANSFER_START'
            // (user must send the stellar asset to the receiving distribution account)
            foreach ($receiverAccountsIds as $receiverAccountId) {

                $waitingTransactions = $this->getWaitingTransactions($receiverAccountId);

                if (count($waitingTransactions) === 0) {
                    // no waiting transactions
                    Log::debug('no waiting transactions found for ' . $receiverAccountId . PHP_EOL);
                    continue;
                }
                /**
                 * @var ReceivedPaymentsQueryResult $paymentsQueryResponse
                 */
                $paymentsQueryResponse = null;

                try {
                    // query received payments.
                    // get the last paging token, so that we do not query too much into the past
                    $lastPagingToken = $this->getLastPagingToken($receiverAccountId);
                    $paymentsQueryResponse = PaymentsHelper::queryReceivedPayments(
                        horizonUrl: $this->horizonUrl,
                        receiverAccountId: $receiverAccountId,
                        cursor: $lastPagingToken,
                    );
                } catch (AccountNotFound) {
                    // distribution account does not exist on Stellar! maybe testnet reset?
                    Log::error(message: 'Distribution account ' . $receiverAccountId .
                        ' not found in horizon ' . $this->horizonUrl . PHP_EOL,
                        context: ['Job:Sep6WithdrawalsWatcher']);
                } catch (HorizonRequestException $e) {
                    SepHelper::logHorizonRequestException($e,
                        context: ['Job:Sep6WithdrawalsWatcher']);
                }

                if ($paymentsQueryResponse === null || count($paymentsQueryResponse->receivedPayments) === 0) {
                    // no payments found.
                    continue;
                }

                /**
                 * @var array<ReceivedPayment> $receivedPayments
                 */
                $receivedPayments = $paymentsQueryResponse->receivedPayments;
                foreach ($waitingTransactions as $waitingTransaction) {
                    // find the payments that match this transaction
                    /**
                     * @var array<ReceivedPayment> $paymentsForTransaction
                     */
                    $paymentsForTransaction = $this->filterPayments(
                        receivedPayments: $receivedPayments,
                        memoValue: $waitingTransaction->memo,
                        memoType: $waitingTransaction->memo_type,
                        assetCode: $waitingTransaction->request_asset_code,
                        assetIssuer: $waitingTransaction->request_asset_issuer,
                    );

                    // add your business logic here to handle the payments correctly
                    // the following is only fake business logic
                    $this->fakeHandlePaymentsForTransaction(
                        transaction: $waitingTransaction,
                        receivedPayments: $paymentsForTransaction,
                        anchorAssets: $anchorAssets,
                        pagingToken: $paymentsQueryResponse->lastTransactionPagingToken,
                    );

                    // we don't need these anymore
                    $receivedPayments = array_diff($receivedPayments, $paymentsForTransaction);
                }
            }
        } catch (Exception $e) {
            Log::error(
                message: $e->getTraceAsString() . PHP_EOL,
                context: ['Job:Sep6WithdrawalsWatcher'],
            );
        }
    }


    /**
     * Selects distinct all distribution accounts that are set as anchor receiver accounts (withdraw_anchor_account)
     * for all waiting transactions.
     *
     * @return array<string> account ids of the distribution accounts that wait for payments.
     */
    private function getReceiverAnchorAccountsIds(): array {
        $receiverAnchorAccounts = Sep06Transaction::distinct()
            ->where('status', '=', Sep06TransactionStatus::PENDING_USER_TRANSFER_START)
            ->whereIn('kind', [Sep06Helper::KIND_WITHDRAW, Sep06Helper::KIND_WITHDRAW_EXCHANGE])
            ->groupBy('withdraw_anchor_account')
            ->get(['withdraw_anchor_account']);

        /**
         * @var array<string> $accountIds
         */
        $accountIds = [];
        if($receiverAnchorAccounts !== null) {
            foreach ($receiverAnchorAccounts as $account) {
                $accountIds[] = $account->withdraw_anchor_account;
            }
        }
        return $accountIds;

    }

    /**
     * Finds the last relevant paging token used to query payments for the given distribution account.
     * @param string $receiverAccountId the id of the distribution account that waits to receive payments.
     * @return string|null if a paging token has been found it is returned, otherwise null
     */
    private function getLastPagingToken(string $receiverAccountId): ?string {
        // load the last paging token used from the last completed transaction
        $stellarPagingToken = Sep06Transaction::where(
            'status',
            '=',
            Sep06TransactionStatus::COMPLETED,
        )->whereIn('kind', [Sep06Helper::KIND_WITHDRAW, Sep06Helper::KIND_WITHDRAW_EXCHANGE])
            ->where('withdraw_anchor_account', '=', $receiverAccountId)
            ->orderBy('tx_completed_at', 'desc')->limit(1)->get(['stellar_paging_token']);

        return $stellarPagingToken?->first()?->stellar_paging_token;
    }

    /**
     * Selects all waiting transactions that have the given distribution account set as a receiver account.
     * @param string $receiverAccountId the id of the distribution account that waits to receive payments.
     * @return array<Sep06Transaction> the array of found waiting transactions.
     */
    private function getWaitingTransactions(string $receiverAccountId): array {
        $waitingDbTransactions = Sep06Transaction::where(
            'status',
            '=',
            Sep06TransactionStatus::PENDING_USER_TRANSFER_START,
        )->whereIn('kind', [Sep06Helper::KIND_WITHDRAW, Sep06Helper::KIND_WITHDRAW_EXCHANGE])
            ->where('withdraw_anchor_account', '=', $receiverAccountId)
            ->get();

        /**
         * @var array<Sep06Transaction> $transactions
         */
        $transactions = [];
        if ($waitingDbTransactions !== null && count($waitingDbTransactions) !== 0) {
            foreach ($waitingDbTransactions as $waitingDbTransaction) {
                if ($waitingDbTransaction instanceof Sep06Transaction) {
                    $transactions[] = $waitingDbTransaction;
                }
            }
        }
        return $transactions;
    }

    private function getAnchorAssets() : array {
        $dbAnchorAssets = AnchorAsset::whereSep06Enabled(true)->get();
        /**
         * @var array<AnchorAsset> $anchorAssets
         */
        $anchorAssets = [];
        if ($dbAnchorAssets !== null && count($dbAnchorAssets) !== 0) {
            foreach ($dbAnchorAssets as $dbAnchorAsset) {
                if ($dbAnchorAsset instanceof AnchorAsset) {
                    $anchorAssets[] = $dbAnchorAsset;
                }
            }
        }
        return $anchorAssets;
    }

    /**
     * Extracts the received payments from the given array that match the
     * values of the parameters (memoValue, memoType, assetCode, assetIssuer)
     *
     * @param array<ReceivedPayment> $receivedPayments the array of received payments to extract from
     * @param ?string $memoValue the extracted payments must have this memo value (including null)
     * @param ?string $memoType the extracted payments must have this memo type (including null)
     * @param ?string $assetCode the extracted payments must have this asset code (including null)
     * @param ?string $assetIssuer the extracted payments must have this asset issuer (including null)
     * @return array<ReceivedPayment> the found payments
     */
    private function filterPayments(
        array $receivedPayments,
        ?string $memoValue,
        ?string $memoType,
        ?string $assetCode,
        ?string $assetIssuer,
    ): array {
        /**
         * @var array<ReceivedPayment> $result
         */
        $result = [];
        foreach ($receivedPayments as $receivedPayment) {
            if ($receivedPayment->memoValue === $memoValue &&
            $receivedPayment->memoType === $memoType &&
            $receivedPayment->assetCode === $assetCode &&
            $receivedPayment->assetIssuer === $assetIssuer) {
                $result[] = $receivedPayment;
            }
        }

        return $result;
    }

    /**
     * Fake business logic to handle received payments for a waiting transaction.
     * @param Sep06Transaction $transaction the waiting transaction, that received the payments
     * @param array<ReceivedPayment> $receivedPayments the received payments for this transaction.
     * @param array<AnchorAsset> $anchorAssets anchor assets from get info.
     * @param string $pagingToken paging token of the last transaction queried.
     * @return void
     */
    private function fakeHandlePaymentsForTransaction(
        Sep06Transaction $transaction,
        array $receivedPayments,
        array $anchorAssets,
        string $pagingToken,
    ) : void {

        if(count($receivedPayments) === 0) {
            Log::debug('no payments received for transaction: ' . $transaction->id . PHP_EOL);
            return;
        }

        $assetCode = $transaction->request_asset_code;
        $assetIssuer = $transaction->request_asset_issuer;
        $anchorAsset = $this->findAnchorAsset($anchorAssets, $assetCode, $assetIssuer);
        if ($anchorAsset === null) {
            $msg = 'could not find anchor asset for transaction ('. $transaction->id . ') asset code: '
                . $assetCode . ' and issuer: ' . $assetIssuer ;
            Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher', 'fakeHandlePaymentsForTransaction']);
            $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, 2);
            return;
        }
        /**
         * @var ?Sep38Quote $quote
         */
        $quote = null;
        if ($transaction->kind === Sep06Helper::KIND_WITHDRAW_EXCHANGE && $transaction->quote_id !== null) {
            $quote = $this->getQuoteForId(
                quoteId: $transaction->quote_id,
                sep10AccountId: $transaction->sep10_account,
                sep10AccountMemo: $transaction->sep10_account_memo,
            );
            if ($quote === null) {
                $msg =  'Could not find quote for id: ' . $transaction->quote_id;
                Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
                $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
                return;
            } if ($quote->expiresAt > new DateTime()) {
                $msg = 'Quote id: ' . $transaction->quote_id . ' has expired';
                Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
                $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
                return;
            }
        }

        if ($transaction->amount_in_asset === null) {
            $msg = 'Transaction: ' . $transaction->id . ' has no amount in asset set';
            Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
            $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
            return;
        }

        if ($transaction->amount_out_asset === null) {
            $msg = 'Transaction: ' . $transaction->id . ' has no amount out asset set';
            Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
            $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
            return;
        }

        /**
         * @var ?Sep38Rate $exchangeRate
         */
        $exchangeRate = null;

        if ($quote === null) {
            $exchangeRate = Sep38Rate::where('sell_asset', '=', $transaction->amount_in_asset)
                ->where('buy_asset', '=', $transaction->amount_out_asset)->first();

            if ($exchangeRate === null) {
                // this exchange is not supported anymore, maybe deleted in db ...
                $msg = 'Transaction: ' . $transaction->id . ' has no exchange rate';
                Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
                $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
                return;
            }
        }

        /**
         * @var array<ReceivedPayment> $paymentsToRefund
         */
        $paymentsToRefund = [];
        $validPaymentFound = false;
        foreach ($receivedPayments as $receivedPayment) {
            // we only consider the first valid payment if any, refund the rest
            if ($receivedPayment->senderAccountId === $transaction->from_account &&
                !$validPaymentFound) {

                $paymentAmount = round(
                    num: floatval($receivedPayment->amountInAsDecimalString),
                    precision: $anchorAsset->significant_decimals,
                );

                // KIND_WITHDRAW_EXCHANGE & quote
                if ($quote !== null) {
                    // the data has already been set when the transaction has been created
                    // we only have to check if the payment amount matches the sell amount from the quote
                    $quoteSellAmount = round(
                        num: floatval($quote->sellAmount),
                        precision: $anchorAsset->significant_decimals,
                    );
                    if ($paymentAmount !== $quoteSellAmount) {
                        $paymentsToRefund[] = $receivedPayment;
                        continue;
                    }
                } else if ($exchangeRate !== null) {
                    // based on exchange rate
                    if ($transaction->amount_in !== null && $paymentAmount != $transaction->amount_in) {
                        $paymentsToRefund[] = $receivedPayment;
                        continue;
                    }
                    $transaction->amount_in  = $paymentAmount;

                    $feePercent = $exchangeRate->fee_percent;
                    $fee = $paymentAmount * ($feePercent / 100);
                    $paymentAmountMinusFee = $paymentAmount - $fee;
                    $transaction->amount_out = $paymentAmountMinusFee * $exchangeRate->rate;
                    try {
                        $feeInfo = new TransactionFeeInfo(
                            total: strval($fee),
                            asset: IdentificationFormatAsset::fromString($transaction->amount_in_asset),
                            details: [new TransactionFeeInfoDetail(
                                name: 'Service fee',
                                amount: strval($fee))]);
                        $transaction->fee_details = json_encode($feeInfo->toJson());
                    } catch (InvalidAsset) {
                        $msg = 'Transaction: ' . $transaction->id . ' has invalid amount in asset';
                        Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
                        $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
                        return;
                    }
                } else {
                    $msg = 'Transaction: ' . $transaction->id . ' has no exchange rate or quote';
                    Log::error(message: $msg . PHP_EOL, context: ['Job:Sep6WithdrawalsWatcher']);
                    $this->setTxToErrorAndRefundAll($transaction, $msg, $receivedPayments, $anchorAsset->significant_decimals);
                    return;
                }

                $transaction->external_transaction_id = strval(rand(5000000, 150000000));
                $stellarTransactionId = $receivedPayment->stellarTransactionId;
                $transaction->stellar_transaction_id = $stellarTransactionId;
                $transaction->stellar_paging_token = $pagingToken;
                $transaction->status = Sep06TransactionStatus::COMPLETED;
                $transaction->tx_completed_at = (new DateTime())->format(DateTimeInterface::ATOM);
                $transaction->save();
                $this->maybeMakeCallback($transaction->id);
                $validPaymentFound = true;
            } else {
                // refund payment
                $paymentsToRefund[] = $receivedPayment;
            }
        }
        foreach ($paymentsToRefund as $paymentToRefund) {
            $this->refundPayment($paymentToRefund, $transaction, $anchorAsset->significant_decimals);
        }
    }

    /**
     * Updates the transaction status to ERROR and saves it to the db.
     * Refunds all payments associated with the transaction and updates
     * the transaction refunds data in the db correspondingly.
     * @param Sep06Transaction $transaction transaction that has an error
     * @param string $errorMessage error message to be used in the transaction.
     * @param array<ReceivedPayment> $paymentsToRefund the payments to be refunded.
     * @param int $significantDecimals significant decimals of the payment asset used in the payments.
     * @return void
     */
    private function setTxToErrorAndRefundAll(
        Sep06Transaction $transaction,
        string $errorMessage,
        array $paymentsToRefund,
        int $significantDecimals,
    ): void
    {
        $transaction->status = Sep06TransactionStatus::ERROR;
        $transaction->message = $errorMessage;
        $transaction->save();
        $this->maybeMakeCallback($transaction->id);
        foreach ($paymentsToRefund as $payment) {
            $this->refundPayment($payment, $transaction, $significantDecimals);
        }
    }

    /**
     * Refunds a received payment associated with the given transaction and updates the transaction data
     * by storing the refunds info.
     * @param ReceivedPayment $payment the payment to be refunded
     * @param Sep06Transaction $transaction the transaction associated with the payment to be refunded.
     * @param int $significantDecimals significant decimals of the payment asset.
     * @return void
     */
    private function refundPayment(
        ReceivedPayment $payment,
        Sep06Transaction $transaction,
        int $significantDecimals,
    ) :void {
        if ($payment->assetIssuer === null) {
            return;
        }
        $distributionAccKp = $this->getDistributionAccountKeyPairForAsset($payment->assetCode, $payment->assetIssuer);
        if ($distributionAccKp === null) {
            // distribution account not found
            Log::error(message: 'Distribution account not found for asset code ' . $payment->assetCode .
                ' and asset issuer ' . $payment->assetIssuer . ' to be able to refund payment with tx hash ' .
            $payment->stellarTransactionId . ' for sep-6 transaction with id ' . $transaction->id . PHP_EOL,
                context: ['Job:Sep6WithdrawalsWatcher', 'refundPayment']);
            return;
        }

        $distributionAccountId = $distributionAccKp->getAccountId();

        /**
         * @var ?string $stellarTransactionId
         */
        $stellarTransactionId = null;

        try {
            $stellarSDK = new StellarSDK($this->horizonUrl);
            $distributionAccount = $stellarSDK->requestAccount($distributionAccountId);
            $paymentOp = (new PaymentOperationBuilder(
                destinationAccountId: $payment->senderAccountId,
                asset: AssetTypeCreditAlphanum::createNonNativeAsset($payment->assetCode, $payment->assetIssuer),
                amount: $payment->amountInAsDecimalString))->build();
            $memo = Memo::none();
            if ($transaction->refund_memo !== null && $transaction->refund_memo_type !== null) {
                try {
                    $memo = MemoHelper::makeMemoFromSepRequestData($transaction->memo, $transaction->memo_type);
                } catch (InvalidSepRequest $e) {
                    $memo = Memo::none();
                }
            }

            $txBuilder = (new TransactionBuilder($distributionAccount))
                ->setMaxOperationFee(1000)
                ->addOperation($paymentOp)
                ->addMemo($memo);

            $tx = $txBuilder->build();
            $network = new Network($this->networkPassphrase);
            $tx->sign($distributionAccKp, $network);
            $response = $stellarSDK->submitTransaction($tx);

            if ($response->isSuccessful()) {
                $stellarTransactionId = $response->getHash();
            }

        } catch (HorizonRequestException $e) {
            Log::error(message: 'Could not refund payment with tx hash ' .
                $payment->stellarTransactionId . ' for sep-6 transaction with id ' . $transaction->id . PHP_EOL,
                context: ['Job:Sep6WithdrawalsWatcher', 'refundPayment']);
            SepHelper::logHorizonRequestException(e: $e, context: ['Job:Sep6WithdrawalsWatcher', 'refundPayment']);
        }

        if ($stellarTransactionId === null) {
            Log::error(message: 'Could not refund payment with tx hash ' .
                $payment->stellarTransactionId . ' for sep-6 transaction with id ' . $transaction->id . PHP_EOL,
                context: ['Job:Sep6WithdrawalsWatcher', 'refundPayment']);
            return;
        }

        $refundsJson = $transaction->refunds;
        /**
         * @var ?TransactionRefunds $transactionRefunds
         */
        $transactionRefunds = null;
        if ($refundsJson !== null) {
            $transactionRefunds = SepHelper::parseRefunds($refundsJson);
        }

        $amountRefunded = round(
            num: floatval($payment->amountInAsDecimalString),
            precision: $significantDecimals,
        );
        $amountFee = 0.0;
        $transactionRefundPayment = new TransactionRefundPayment(
            id: $stellarTransactionId,
            idType: 'stellar',
            amount: strval($amountRefunded),
            fee: strval($amountFee),
        );
        /**
         * @var array<TransactionRefundPayment> $transactionRefundPayments
         */
        $transactionRefundPayments = [$transactionRefundPayment];

        if ($transactionRefunds !== null) {
            $amountRefunded += floatval($transactionRefunds->amountRefunded);
            $amountFee += floatval($transactionRefunds->amountFee);
            $transactionRefundPayments = array_merge($transactionRefundPayments, $transactionRefunds->payments);
        }
        $newTransactionRefunds = new TransactionRefunds(
            amountRefunded: strval($amountRefunded),
            amountFee: strval($amountFee),
            payments: $transactionRefundPayments,
        );
        $transaction->refunds = json_encode($newTransactionRefunds->toJson());
        $transaction->save();
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
     * Loads a quote from the database.
     * @param string $quoteId if of the quote to load
     * @param string $sep10AccountId account id of the user used for sep10 auth
     * @param string|null $sep10AccountMemo account memo of the user used for sep10 auth
     * @return Sep38Quote|null the quote if found.
     */
    private function getQuoteForId(string $quoteId, string $sep10AccountId, ?string $sep10AccountMemo): ?Sep38Quote {
        try {
            return Sep38Helper::getQuoteById(id: $quoteId, accountId: $sep10AccountId, accountMemo: $sep10AccountMemo);
        } catch (QuoteNotFoundForId) {
            Log::error(message: 'Could not find quote for id: ' . $quoteId, context: ['Sep6WithdrawalsWatcher']);
        } catch (AnchorFailure $e) {
            Log::error(message: 'Anchor failure: ' . $e->getMessage() . ' while getting quote for id: ' . $quoteId,
                context: ['Sep6WithdrawalsWatcher']);
        }
        return null;
    }

    /**
     * Iterates a list of anchor assets to find the asset given by code and issuer.
     * @param array<AnchorAsset> $allAnchorAssets the list of assets
     * @param string $assetCode the asset code of the asset to be found
     * @param string|null $assetIssuer the asset issuer of the asset to be found.
     * @return ?AnchorAsset the found anchor asset if any.
     */
    private function findAnchorAsset(array $allAnchorAssets, string $assetCode, ?string $assetIssuer): ?AnchorAsset {
        foreach($allAnchorAssets as $anchorAsset) {
            if($anchorAsset->code === $assetCode &&
                $anchorAsset->issuer === $assetIssuer) {
                return $anchorAsset;
            }
        }
        return null;
    }

    private function maybeMakeCallback(string $sep6TransactionId) : void {

    }
}
