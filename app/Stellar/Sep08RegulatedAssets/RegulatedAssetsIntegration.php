<?php

namespace App\Stellar\Sep08RegulatedAssets;

use App\Models\Sep08KycStatus;
use App\Stellar\StellarAppConfig;
use ArgoNavis\PhpAnchorSdk\callback\ApprovalActionRequired;
use ArgoNavis\PhpAnchorSdk\callback\ApprovalPending;
use ArgoNavis\PhpAnchorSdk\callback\ApprovalRejected;
use ArgoNavis\PhpAnchorSdk\callback\ApprovalRevised;
use ArgoNavis\PhpAnchorSdk\callback\ApprovalSuccess;
use ArgoNavis\PhpAnchorSdk\callback\IRegulatedAssetsIntegration;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Throwable;

use function json_encode;

class RegulatedAssetsIntegration implements IRegulatedAssetsIntegration
{

    /**
     * @inheritDoc
     */
    public function approve(string $tx):
    ApprovalSuccess|ApprovalRevised|ApprovalPending|ApprovalActionRequired|ApprovalRejected
    {

        try {
            $assetCode = config('stellar.sep08.asset_code');
            $assetIssuerId = config('stellar.sep08.asset_issuer_id');
            $issuerSigningKey = config('stellar.sep08.issuer_signing_key');
            $transaction = Transaction::fromEnvelopeBase64XdrString($tx);

            Log::debug(
                'Approving transaction.',
                ['context' => 'sep08', 'asset_code' => $assetCode,
                    'asset_issuer_id' => $assetIssuerId, 'transaction_string' => $tx,
                ],
            );

            if (!($transaction instanceof Transaction)) {
                Log::warning(
                    'The approval is rejected, transaction type is not supported.',
                    ['context' => 'sep08'],
                );

                return new ApprovalRejected(error: "Transaction type is not supported");
            }

            $sourceAccountId = $transaction->getSourceAccount()->getAccountId();
            if ($sourceAccountId === $assetIssuerId) {
                Log::warning(
                    'The approval is rejected, transaction source account can not be issuer account.',
                    ['context' => 'sep08', 'source_account_id' => $sourceAccountId,
                        'asset_issuer_id' => $assetIssuerId,
                    ],
                );

                return new ApprovalRejected(error: "Transaction source account can not be issuer account.");
            }

            $noOperations = count($transaction->getOperations());
            if ($noOperations != 1) {
                Log::warning(
                    'The approval is rejected, the transaction must 
                        contain exactly one operation of type payment',
                    ['context' => 'sep08', 'no_operations' => $noOperations],
                );

                return new ApprovalRejected(
                    error: "Please submit a transaction with exactly one operation of type payment.",
                );
            }

            $paymentOp = $transaction->getOperations()[0];
            if (!($paymentOp instanceof PaymentOperation)) {
                Log::warning(
                    'The approval is rejected, there is an unauthorized operation in the provided transaction.',
                    ['context' => 'sep08'],
                );

                // must contain a payment operation.
                return new ApprovalRejected(error: "There is an unauthorized operation in the provided transaction.");
            }

            $opSourceAccountId = $paymentOp->getSourceAccount()?->getAccountId();
            if ($opSourceAccountId === $assetIssuerId) {
                Log::warning(
                    'The approval is rejected, payment operation source account can not be issuer account.',
                    ['context' => 'sep08', 'op_source_account_id' => $opSourceAccountId,
                        'asset_issuer_id' => $assetIssuerId,
                    ],
                );

                return new ApprovalRejected(error: "Payment operation source account can not be issuer account.");
            }

            $opDestinationAccountId = $paymentOp->getDestination()->getAccountId();
            if ($opDestinationAccountId === $assetIssuerId) {
                Log::warning(
                    'The approval is rejected, can\'t transfer asset to its issuer.',
                    ['context' => 'sep08', 'op_destination_account_id' => $opDestinationAccountId,
                        'asset_issuer_id' => $assetIssuerId,
                    ],
                );

                return new ApprovalRejected(error: "Can't transfer asset to its issuer.");
            }

            $paymentAsset = $paymentOp->getAsset();
            if (!($paymentAsset instanceof AssetTypeCreditAlphanum)) {
                Log::warning(
                    'The approval is rejected, the payment asset is not supported by this issuer.',
                    ['context' => 'sep08'],
                );

                return new ApprovalRejected(error: "The payment asset is not supported by this issuer.");
            }

            if ($paymentAsset->getCode() !== $assetCode || $paymentAsset->getIssuer() !== $assetIssuerId) {
                Log::warning(
                    'The approval is rejected, the payment asset is not supported by this issuer.',
                    ['context' => 'sep08', 'payment_asset_code' => $paymentAsset->getCode(), 'asset_code' => $assetCode,
                        'payment_asset_issuer_id' => $paymentAsset->getIssuer(), 'asset_issuer_id' => $assetIssuerId,
                    ],
                );

                return new ApprovalRejected(error: "The payment asset is not supported by this issuer.");
            }

            if ($opSourceAccountId !== null && $opSourceAccountId !== $sourceAccountId) {
                Log::warning(
                    'The approval is rejected, payment source account must be
                                the same as the transaction source account.',
                    ['context' => 'sep08', 'op_source_account_id' => $opSourceAccountId,
                        'source_account_id' => $sourceAccountId,
                    ],
                );

                return new ApprovalRejected(
                    error: "Payment source account must be the same as the transaction source account.",
                );
            }

            $senderDetails = $this->getAccountDetails($sourceAccountId);
            if ($senderDetails === null) {
                Log::warning(
                    'The approval is rejected, transaction source account must exist on the Stellar network.',
                    ['context' => 'sep08', 'sender_details' => 'null'],
                );

                return new ApprovalRejected(error: "Transaction source account must exist on the Stellar network.");
            }

            $txSequenceNr = $transaction->getSequenceNumber();
            $incrementedSourceSequenceNr = $senderDetails->getIncrementedSequenceNumber();
            if (!$txSequenceNr->equals($incrementedSourceSequenceNr)) {
                Log::warning(
                    'The approval is rejected, invalid transaction sequence number.',
                    ['context' => 'sep08', 'tx_sequence_nr' => $txSequenceNr,
                        'incremented_source_sequence_nr' => $incrementedSourceSequenceNr,
                    ],
                );

                return new ApprovalRejected(error: "Invalid transaction sequence number.");
            }

            $kycResponse = $this->handleKyc($sourceAccountId, $paymentOp);
            if ($kycResponse !== false) {
                Log::warning(
                    'Either the KYC is rejected or KYC action is required.',
                    ['context' => 'sep08', 'kyc_response' => json_encode($kycResponse)],
                );

                return $kycResponse;
            }


            // Build the transaction
            $txBuilder = new TransactionBuilder($senderDetails);
            $allowTrustSourceOp = (
                new AllowTrustOperationBuilder(
                    trustor: $sourceAccountId,
                    assetCode: $paymentAsset->getCode(),
                    authorized: true,
                    authorizedToMaintainLiabilities: false,
                )
            )->setSourceAccount($assetIssuerId)->build();

            $allowTrustDestOp = (
                new AllowTrustOperationBuilder(
                    trustor: $opDestinationAccountId,
                    assetCode: $paymentAsset->getCode(),
                    authorized: true,
                    authorizedToMaintainLiabilities: false,
                )
            )->setSourceAccount($assetIssuerId)->build();

            $disAllowTrustDestOp = (
                new AllowTrustOperationBuilder(
                    trustor: $opDestinationAccountId,
                    assetCode: $paymentAsset->getCode(),
                    authorized: false,
                    authorizedToMaintainLiabilities: false,
                )
            )->setSourceAccount($assetIssuerId)->build();

            $disAllowTrustSourceOp = (
                new AllowTrustOperationBuilder(
                    trustor: $sourceAccountId,
                    assetCode: $paymentAsset->getCode(),
                    authorized: true,
                    authorizedToMaintainLiabilities: false,
                )
            )->setSourceAccount($assetIssuerId)->build();

            $txBuilder->addOperation($allowTrustSourceOp);
            $txBuilder->addOperation($allowTrustDestOp);
            $txBuilder->addOperation($paymentOp);
            $txBuilder->addOperation($disAllowTrustDestOp);
            $txBuilder->addOperation($disAllowTrustSourceOp);

            $txBuilder->setMaxOperationFee(300);
            $latestLedgerCloseTime = $this->getLatestLedgerCloseTime();
            if ($latestLedgerCloseTime !== null) {
                $endTime = clone $latestLedgerCloseTime;
                $endTime->modify('+5 minutes');
                $timeBounds = new TimeBounds(
                    $latestLedgerCloseTime,
                    $endTime,
                );
                $preconditions = new TransactionPreconditions();
                $preconditions->setTimeBounds($timeBounds);
                $txBuilder->setPreconditions($preconditions);
            }

            $revisedTransaction = $txBuilder->build();
            $stellarConfig = new StellarAppConfig();
            $revisedTransaction->sign(KeyPair::fromSeed($issuerSigningKey), $stellarConfig->getStellarNetwork());
            Log::debug(
                'The approval succeeded, authorization and deauthorization operations were added.',
                ['context' => 'sep08', 'revised_transaction' => json_encode($revisedTransaction)],
            );

            return new ApprovalRevised(
                tx:$revisedTransaction->toEnvelopeXdrBase64(),
                message:'Authorization and deauthorization operations were added.',
            );
        } catch (Throwable $e) {
            Log::error(
                'Failed to approve the transaction.',
                ['context' => 'sep08', 'error' => $e->getMessage(), 'exception' => $e],
            );

            throw new AnchorFailure(message: $e->getMessage(), code:$e->getCode());
        }
    }

    /**
     * @throws AnchorFailure
     */
    private function handleKyc(
        string $sourceAccount,
        PaymentOperation $paymentOp,
    ): ApprovalActionRequired | ApprovalRejected | ApprovalPending | false {

        try {
            $paymentAmount = floatval($paymentOp->getAmount());
            $maxAmount = floatval(config('stellar.sep08.payment_threshold'));
            Log::debug(
                'Verifying if KYC data needs to be handled.',
                ['context' => 'sep08', 'source_account' => $sourceAccount],
            );

            Log::debug(
                'Verifying amounts.',
                ['context' => 'sep08', 'payment_amount' => $paymentAmount, 'max_amount' => $maxAmount],
            );
            if ($paymentAmount <= $maxAmount) {
                return false;
            }

            $kycData = Sep08KycStatus::whereStellarAddress($sourceAccount)->first();
            if ($kycData === null || (!$kycData->approved && !$kycData->rejected && !$kycData->pending)) {
                // create new entry if needed and return ApprovalActionRequired
                if ($kycData === null) {
                    Log::debug('SEP-08 KYC data is null, creating a new record.', ['context' => 'sep08']);

                    $newKycData = new Sep08KycStatus;
                    $newKycData->stellar_address = $sourceAccount;
                    $newKycData->save();
                }

                Log::debug(
                    'SEP-08 KYC data missing.',
                    ['context' => 'sep08', 'missing_fields' => 'email_address'],
                );

                return new ApprovalActionRequired(
                    message: "Please provide your email address.",
                    actionUrl: config('stellar.sep08.kyc_status_endpoint') . '/' .$sourceAccount,
                    actionMethod: "POST",
                    actionFields: ["email_address"],
                );
            } elseif ($kycData->rejected) {
                Log::debug('SEP-08 KYC data is rejected.', ['context' => 'sep08']);

                return new ApprovalRejected(
                    "Your KYC was rejected and you're not authorized for operations above " . strval($maxAmount) .
                    ' ' . config('stellar.sep08.asset_code'),
                );
            } elseif ($kycData->approved) {
                Log::debug('SEP-08 KYC data is approved.', ['context' => 'sep08']);

                return false;
            } else {
                Log::debug('SEP-08 KYC data approval is pending.', ['context' => 'sep08']);

                return new ApprovalPending(timeout: 1000, message: 'Your approval request is pending. Please try again later.');
            }
        } catch (Throwable $e) {
            Log::debug(
                'Failed the KYC data verification.',
                ['context' => 'sep08', 'error' => $e->getMessage(), 'exception' => $e],
            );

            throw new AnchorFailure(message: $e->getMessage(), code:$e->getCode());
        }
    }

    private function getAccountDetails(string $accountId): ?AccountResponse
    {
        try {
            $stellarConfig = new StellarAppConfig();
            $sdk = new StellarSDK($stellarConfig->getHorizonUrl());
            return $sdk->requestAccount($accountId);
        } catch (HorizonRequestException $e) {
            Log::error(
                'Account not found.',
                ['context' => 'sep08', 'error' => $e->getMessage(), 'exception' => $e, 'account_id' => $accountId],
            );

            // account not found.
            return null;
        }
    }

    private function getLatestLedgerCloseTime(): ?DateTime
    {
        try {
            $stellarConfig = new StellarAppConfig();
            $sdk = new StellarSDK($stellarConfig->getHorizonUrl());
            $response = $sdk->ledgers()->limit(1)->order("desc")->execute();
            $ledgerCloseTime = $response->getLedgers()->toArray()[0]->getClosedAt();
            Log::debug(
                'Ledger latest close time has been retrieved successfully.',
                ['context' => 'sep08', 'ledger_close_time' => $ledgerCloseTime],
            );

            return DateTime::createFromFormat(DateTimeInterface::ATOM, $ledgerCloseTime);
        } catch (Throwable $e) {
            Log::error(
                'Failed to establish the ledger close time.',
                ['context' => 'sep08', 'error' => $e->getMessage(), 'exception' => $e],
            );

            return null;
        }
    }
}
