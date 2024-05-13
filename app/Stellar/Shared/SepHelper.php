<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Shared;

use ArgoNavis\PhpAnchorSdk\shared\TransactionRefundPayment;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefunds;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;

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
    public static function parseRefunds(string $refundsJson) : ?TransactionRefunds {
        $refunds = json_decode($refundsJson, true);

        if ($refunds != null) {
            if (isset($refunds['amount_refunded']) && is_string($refunds['amount_refunded'])
                && isset($refunds['amount_fee']) && is_string($refunds['amount_fee'])
                && isset($refunds['payments']) && is_array($refunds['payments'])) {

                /**
                 * @var array<TransactionRefundPayment> $payments
                 */
                $payments = [];
                foreach($refunds['payments'] as $payment) {
                    if(isset($payment['id']) && is_string($payment['id'])
                        && isset($payment['id_type']) && is_string($payment['id_type'])
                        && isset($payment['amount']) && is_string($payment['amount'])
                        && isset($payment['fee']) && is_string($payment['fee']) ) {

                        $payment = new TransactionRefundPayment(
                            id: $payment['id'],
                            idType: $payment['id_type'],
                            amount: $payment['amount'],
                            fee: $payment['fee']
                        );

                        $payments[] = $payment;
                    }
                }
                return new TransactionRefunds(
                    amountRefunded: $refunds['amount_refunded'],
                    amountFee: $refunds['amount_fee'],
                    payments: $payments,
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
    public static function parseFeeDetails(string $feeDetailsJson) : ?TransactionFeeInfo {
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
                        foreach($feeDetails['details'] as $detail) {
                            if(isset($detail['name']) && is_string($detail['name'])
                                && isset($detail['amount']) && is_string($detail['amount']) ) {
                                $feeDetail = new TransactionFeeInfoDetail(
                                    name:$detail['name'],
                                    amount:$detail['amount'],
                                );
                                if (isset($detail['description']) && is_string($detail['description'])) {
                                    $feeDetail->description = $detail['description'];
                                }
                                $details[] = $feeDetail;
                            }
                        }
                        $feeInfo->details = $details;
                    }
                    return $feeInfo;
                } catch (InvalidAsset) {
                    // todo: logging
                }
            }
        }
        return null;
    }
}