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
            if(!($transaction instanceof Transaction)) {
                return new ApprovalRejected(error: "Transaction type is not supported");
            }

            $sourceAccountId = $transaction->getSourceAccount()->getAccountId();
            if($sourceAccountId === $assetIssuerId) {
                return new ApprovalRejected(error: "Transaction source account can not be issuer account.");
            }

            if(count($transaction->getOperations()) != 1) {
                return new ApprovalRejected(
                    error: "Please submit a transaction with exactly one operation of type payment.",
                );
            }

            $paymentOp = $transaction->getOperations()[0];
            if(!($paymentOp instanceof PaymentOperation)) {
                // must contain a payment operation.
                return new ApprovalRejected(error: "There is an unauthorized operation in the provided transaction.");
            }

            $opSourceAccountId = $paymentOp->getSourceAccount()?->getAccountId();
            if($opSourceAccountId === $assetIssuerId) {
                return new ApprovalRejected(error: "Payment operation source account can not be issuer account.");
            }

            $opDestinationAccountId = $paymentOp->getDestination()->getAccountId();
            if($opDestinationAccountId === $assetIssuerId) {
                return new ApprovalRejected(error: "Can't transfer asset to its issuer.");
            }

            $paymentAsset = $paymentOp->getAsset();
            if(!($paymentAsset instanceof AssetTypeCreditAlphanum)) {
                return new ApprovalRejected(error: "The payment asset is not supported by this issuer.");
            }

            if($paymentAsset->getCode() !== $assetCode || $paymentAsset->getIssuer() !== $assetIssuerId) {
                return new ApprovalRejected(error: "The payment asset is not supported by this issuer.");
            }

            if($opSourceAccountId !== null && $opSourceAccountId !== $sourceAccountId) {
                return new ApprovalRejected(
                    error: "Payment source account must be the same as the transaction source account.",
                );
            }

            $senderDetails = $this->getAccountDetails($sourceAccountId);
            if($senderDetails === null) {
                return new ApprovalRejected(error: "Transaction source account must exist on the Stellar network.");
            }

            $txSequenceNr = $transaction->getSequenceNumber();
            $incrementedSourceSequenceNr = $senderDetails->getIncrementedSequenceNumber();
            if(!$txSequenceNr->equals($incrementedSourceSequenceNr)) {
                return new ApprovalRejected(error: "Invalid transaction sequence number.");
            }

            $kycResponse = $this->handleKyc($sourceAccountId, $paymentOp);
            if($kycResponse !== false) {
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

            return new ApprovalRevised(
                tx:$revisedTransaction->toEnvelopeXdrBase64(),
                message:'Authorization and deauthorization operations were added.',
            );

        } catch (Throwable $e) {
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
            if ($paymentAmount <= $maxAmount) {
                return false;
            }

            $kycData = Sep08KycStatus::whereStellarAddress($sourceAccount)->first();
            if ($kycData === null || (!$kycData->approved && !$kycData->rejected && !$kycData->pending)) {
                // create new entry if needed and return ApprovalActionRequired
                if ($kycData === null) {
                    $newKycData = new Sep08KycStatus;
                    $newKycData->stellar_address = $sourceAccount;
                    $newKycData->save();
                }

                return new ApprovalActionRequired(
                    message: "Please provide your email address.",
                    actionUrl: config('stellar.sep08.kyc_status_endpoint') . '/' .$sourceAccount,
                    actionMethod: "POST",
                    actionFields: ["email_address"],
                );
            } else if ($kycData->rejected) {

                return new ApprovalRejected(
                    "Your KYC was rejected and you're not authorized for operations above " . strval($maxAmount) .
                    ' ' . config('stellar.sep08.asset_code'),
                );
            } else if($kycData->approved) {
                return false;
            } else {
                return new ApprovalPending(timeout: 1000, message: 'Your approval request is pending. Please try again later.');
            }
        } catch (Throwable $e) {
            throw new AnchorFailure(message: $e->getMessage(), code:$e->getCode());
        }
    }

    private function getAccountDetails(string $accountId): ?AccountResponse {
        try {
            $stellarConfig = new StellarAppConfig();
            $sdk = new StellarSDK($stellarConfig->getHorizonUrl());
            return $sdk->requestAccount($accountId);
        } catch(HorizonRequestException $e) {
            // account not found.
            return null;
        }
    }

    private function getLatestLedgerCloseTime(): ?DateTime {
        try {
            $stellarConfig = new StellarAppConfig();
            $sdk = new StellarSDK($stellarConfig->getHorizonUrl());
            $response = $sdk->ledgers()->limit(1)->order("desc")->execute();
            $ledgerCloseTime = $response->getLedgers()->toArray()[0]->getClosedAt();
            return DateTime::createFromFormat(DateTimeInterface::ATOM, $ledgerCloseTime);
        } catch (Throwable $e) {
            return null;
        }
    }
}
