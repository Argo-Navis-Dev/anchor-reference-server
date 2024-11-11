<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Shared;

use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefundPayment;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefunds;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use Exception;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

use function json_encode;

class SepHelper
{
    /**
     * Parses fee refunds into a TransactionRefunds object from a given json string
     * E.g. json string:
     * {
     *  "amount_refunded": "10",
     *  "amount_fee": "5",
     *  "payments": [
     *      {
     *          "id": "b9d0b2292c4e09e8eb22d036171491e87b8d2086bf8b265874c8d182cb9c9020",
     *          "id_type": "stellar",
     *          "amount": "10",
     *          "fee": "5"
     *      }
     *  ]
     * }
     *
     * @param string $refundsJson
     * @return TransactionRefunds|null
     */
    public static function parseRefunds(string $refundsJson) : ?TransactionRefunds
    {
        $refunds = json_decode($refundsJson, true);
        Log::debug(
            'Parsing refunds string.',
            ['context' => 'shared', 'refunds' => json_encode($refundsJson)],
        );

        if ($refunds != null) {
            if (isset($refunds['amount_refunded']) && is_string($refunds['amount_refunded'])
                && isset($refunds['amount_fee']) && is_string($refunds['amount_fee'])
                && isset($refunds['payments']) && is_array($refunds['payments'])) {

                /**
                 * @var array<TransactionRefundPayment> $payments
                 */
                $payments = [];
                foreach ($refunds['payments'] as $payment) {
                    if (isset($payment['id']) && is_string($payment['id'])
                        && isset($payment['id_type']) && is_string($payment['id_type'])
                        && isset($payment['amount']) && is_string($payment['amount'])
                        && isset($payment['fee']) && is_string($payment['fee'])) {
                        $payment = new TransactionRefundPayment(
                            id: $payment['id'],
                            idType: $payment['id_type'],
                            amount: $payment['amount'],
                            fee: $payment['fee']
                        );

                        $payments[] = $payment;
                    } else {
                        Log::warning(
                            'Invalid payment json, id, id_type, amount and fee must be set.',
                            ['context' => 'shared', 'refunds' => json_encode($payment)],
                        );
                    }
                }
                $transactionRefunds = new TransactionRefunds(
                    amountRefunded: $refunds['amount_refunded'],
                    amountFee: $refunds['amount_fee'],
                    payments: $payments,
                );
                Log::debug(
                    'The parsed refunds.',
                    ['context' => 'shared', 'refunds' => json_encode($transactionRefunds)],
                );

                return $transactionRefunds;
            } else {
                Log::warning(
                    'Invalid refunds json, amount_refunded, amount_fee and payments must be set.',
                    ['context' => 'shared', 'refunds' => json_encode($refundsJson)],
                );
            }
        }
        return null;
    }

    /**
     * Parses fee details into a TransactionFeeInfo object from a given json string
     * E.g. json string:
     * {
     *  "total": "8.40",
     *  "asset": "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
     *  "details": [
     *      {
     *          "name": "Service fee",
     *          "amount": "8.40"
     *      }
     *   ]
     * }
     *
     * @param string $feeDetailsJson
     * @return TransactionFeeInfo|null
     */
    public static function parseFeeDetails(string $feeDetailsJson) : ?TransactionFeeInfo
    {
        Log::debug(
            'Parsing transaction fee details string.',
            ['context' => 'shared', 'fee_details' => json_encode($feeDetailsJson)],
        );

        $feeDetails = json_decode($feeDetailsJson, true);

        if ($feeDetails != null) {
            if (isset($feeDetails['total']) && is_string($feeDetails['total'])
                && isset($feeDetails['asset']) && is_string($feeDetails['asset'])) {
                try {
                    $asset = IdentificationFormatAsset::fromString($feeDetails['asset']);
                    $feeInfo = new TransactionFeeInfo(total: $feeDetails['total'], asset: $asset);
                    if (isset($feeDetails['details']) && is_array($feeDetails['details'])) {
                        /**
                         * @var array<TransactionFeeInfoDetail> $details
                         */
                        $details = [];
                        foreach ($feeDetails['details'] as $detail) {
                            if (isset($detail['name']) && is_string($detail['name'])
                                && isset($detail['amount']) && is_string($detail['amount'])) {
                                $feeDetail = new TransactionFeeInfoDetail(
                                    name:$detail['name'],
                                    amount:$detail['amount'],
                                );
                                if (isset($detail['description']) && is_string($detail['description'])) {
                                    $feeDetail->description = $detail['description'];
                                }
                                $details[] = $feeDetail;
                            } else {
                                Log::warning(
                                    'Invalid fee detail json, name and amount must be set.',
                                    ['context' => 'shared', 'fee_detail' => json_encode($detail)],
                                );
                            }
                        }
                        $feeInfo->details = $details;
                    } else {
                        Log::debug(
                            'Invalid fee details json, details must be set.',
                            ['context' => 'shared', 'fee_details' => json_encode($feeDetailsJson)],
                        );
                    }
                    Log::debug(
                        'The parsed fee details.',
                        ['context' => 'shared', 'fee_details' => json_encode($feeInfo)],
                    );

                    return $feeInfo;
                } catch (InvalidAsset $iae) {
                    Log::error(
                        'Invalid asset.',
                        ['context' => 'shared', 'error' =>  $iae->getMessage(), 'exception' => $iae,
                            'asset' => $feeDetails['asset']],
                    );
                }
            } else {
                Log::warning(
                    'Invalid fee details json, total and asset must be set.',
                    ['context' => 'shared', 'fee_details' => json_encode($feeDetailsJson)],
                );
            }
        } else {
            Log::warning(
                'Invalid fee details json.',
                ['context' => 'shared', 'fee_details' => json_encode($feeDetailsJson)],
            );
        }
        Log::debug(
            'The parsed fee details is null.',
            ['context' => 'shared', 'fee_details' => json_encode($feeDetailsJson)],
        );

        return null;
    }
    
    public static function logHorizonRequestException(HorizonRequestException $e, array $context) : void
    {
        Log::error(message: ' HorizonRequestException - requested url: ' . $e->getRequestedUrl(). PHP_EOL, context: $context);
        Log::error(message: ' HorizonRequestException - status code: ' . $e->getStatusCode(). PHP_EOL, context: $context);
        Log::error(message: ' HorizonRequestException - message: ' . $e->getMessage(). PHP_EOL, context: $context);
        $horizonErrorResponse = $e->getHorizonErrorResponse();
        if ($horizonErrorResponse !== null) {
            Log::error(message: ' HorizonRequestException - error response type: ' . $horizonErrorResponse->type. PHP_EOL, context: $context);
            Log::error(message: ' HorizonRequestException - error response title: ' . $horizonErrorResponse->title. PHP_EOL, context: $context);
            Log::error(message: ' HorizonRequestException - error response status: ' . $horizonErrorResponse->status. PHP_EOL, context: $context);
            Log::error(message: ' HorizonRequestException - error response detail: ' . $horizonErrorResponse->detail. PHP_EOL, context: $context);
            if ($horizonErrorResponse->instance !== null) {
                Log::error(message: ' HorizonRequestException - error response instance: ' . $horizonErrorResponse->instance. PHP_EOL, context: $context);
            }
            $extras = $horizonErrorResponse->extras;
            if ($extras?->getResultXdr() !== null) {
                Log::error(message: ' HorizonRequestException - error response extras result xdr: ' . $extras?->getResultXdr(). PHP_EOL, context: $context);
            }
            if ($extras?->getEnvelopeXdr() !== null) {
                Log::error(message:  ' HorizonRequestException - error response extras envelope xdr: ' . $extras?->getEnvelopeXdr(). PHP_EOL, context: $context);
            }
            if ($extras?->getTxHash() !== null) {
                Log::error(message:  ' HorizonRequestException - error response extras tx hash: ' . $extras?->getTxHash(). PHP_EOL, context: $context);
            }
        }
    }

    /**
     * Localizes the passed anchor asset's SEP-12 sender or receiver description.
     *
     * @param string $typeKey The sender or receiver type key.
     * @param string $assetCode The asset code.
     * @param string $defaultDescription The default description from the DB.
     * @param bool $isSender True if the description is for the sender, false if for the receiver.
     * @param string|null $lang The language to localize the description to.
     * @return string The localized description or the default description if not found.
     */
    public static function localizeAssetSep12SenderReceiverDescription(
        string $typeKey,
        string $assetCode,
        string $defaultDescription,
        bool $isSender,
        ?string $lang = 'en',
    ) : string {
        $assetCode = strtolower($assetCode);
        $convertedTypeKey = str_replace("-", "_", $typeKey);
        $senderOrReceiver = $isSender ? 'sender' : 'receiver';
        $langKey = "asset_lang.${assetCode}.sep12.{$senderOrReceiver}.types.${convertedTypeKey}.description";
        $description = __($langKey, [], $lang);
        if ($description === $langKey) {
            $description = $defaultDescription;
        }
        Log::debug('Localizing asset SEP-12 sender/receiver description.', [
            'context' => 'shared',
            'asset_code' => $assetCode,
            'type_key' => $typeKey,
            'sender_or_receiver' => $senderOrReceiver,
            'description' => $description,
        ]);

        return $description;
    }
}
