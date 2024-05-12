<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep31CrossBorder;

use App\Models\AnchorAsset;
use App\Models\Sep31Transaction;
use App\Stellar\Sep38Quote\Sep38Helper;
use ArgoNavis\PhpAnchorSdk\callback\Sep31PostTransactionRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep31TransactionResponse;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep06AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep06InfoField;
use ArgoNavis\PhpAnchorSdk\shared\Sep12Type;
use ArgoNavis\PhpAnchorSdk\shared\Sep31AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep31TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefundPayment;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefunds;
use ArgoNavis\PhpAnchorSdk\util\MemoHelper;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31InfoResponse;
use Throwable;

class Sep31Helper
{
    /**
     * @return array<Sep31AssetInfo> the assets having sep06 support enabled.
     */
    public static function getSupportedAssets(): array {
        /**
         * @var array<Sep06AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep31Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            return $result;
        }
        foreach ($assets as $asset) {
            try {
                $result[] = self::sep31AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error('invalid anchor_asset (id: '. $asset->id . ') in db: ' . $iA->getMessage());
            }
        }

        return $result;
    }

    /**
     * Creates and stores a new transaction from the given request data.
     * @param Sep31PostTransactionRequest $request the request data.
     * @return Sep31Transaction the new created transaction.
     * @throws AnchorFailure if any error occurs.
     */
    public static function newTransaction(Sep31PostTransactionRequest $request) : Sep31Transaction {

        $sep31Transaction = new Sep31Transaction;
        $sep31Transaction->status = Sep31TransactionStatus::PENDING_RECEIVER;
        $start = new DateTime('now');
        $sep31Transaction->tx_started_at = $start->format(DateTimeInterface::ATOM);
        $sep31Transaction->sep10_account = $request->accountId;
        $sep31Transaction->sep10_account_memo = $request->accountMemo;
        $sep31Transaction->amount_expected = $request->amount;
        $sep31Transaction->amount_in = $request->amount;
        $sep31Transaction->amount_in_asset = $request->asset->asset->getStringRepresentation();
        if ($request->destinationAsset !== null) {
            $sep31Transaction->amount_out_asset = $request->destinationAsset->getStringRepresentation();
        }
        $sep31Transaction->quote_id = $request->quoteId;
        $sep31Transaction->sender_id = $request->senderId;
        $sep31Transaction->receiver_id = $request->receiverId;
        if ($request->refundMemo !== null) {
            $memoFields = self::memoFieldsFromMemo($request->memo);
            $sep31Transaction->refund_memo = $memoFields['memo_value'];
            $sep31Transaction->refund_memo_type = $memoFields['memo_type'];
        }
        $sep31Transaction->client_domain = $request->clientDomain;

        // add you business logic here
        // calculate amount out here. if quoteId != null take it from the quote,
        // otherwise calculate with your business logic.
        // $sep31Transaction->amount_out = ...;
        // $sep31Transaction->feeDetails = ...;
        // $sep31Transaction->stellar_account_id = ...;
        // others ... e.g. :
        if ($request->quoteId !== null) {
            try {
                $quote = Sep38Helper::getQuoteById($request->quoteId, $request->sep10Account, $request->sep10AccountMemo);
                $sep31Transaction->fee_details = json_encode($quote->fee->toJson());
                $sep31Transaction->amount_out = $quote->buyAmount;
            } catch (Throwable $e) {
                throw new AnchorFailure(message: $e->getMessage(), code: $e->getCode());
            }
        } else {
            $feeInfo = new TransactionFeeInfo(
                total: '0.1',
                asset: $request->destinationAsset,
                details: [new TransactionFeeInfoDetail(
                    name: 'Service fee',
                    amount: '0.1')]);
            $sep31Transaction->fee_details = json_encode($feeInfo->toJson());
        }

        $sep31Transaction->stellar_memo = strval(rand(5000000, 150000000));
        $sep31Transaction->stellar_memo_typ = 'id';
        if ($request->asset->asset->getCode() === 'USDC') {
            $sep31Transaction->stellar_account_id = 'GAKRN7SCC7KVT52XLMOFFWOOM4LTI2TQALFKKJ6NKU3XWPNCLD5CFRY2';
        } else if ($request->asset->asset->getCode() === 'JPYC') {
            $sep31Transaction->stellar_account_id = 'GCMMCKP2OJXLBZCANRHXSGMMUOGJQKNCHH7HQZ4G3ZFLAIBZY5ODJYO6';
        }

        $sep31Transaction->save();
        $sep31Transaction->refresh();
        return $sep31Transaction;
    }

    /**
     * @throws AnchorFailure
     */
    public static function getTransaction(
        string $id,
        string $accountId,
        ?string $accountMemo = null,
    ) : ?Sep31TransactionResponse {

        $query = ['id' => $id, 'sep10_account' => $accountId];

        if ($accountMemo !== null) {
            $query['sep10_account_memo'] = $accountMemo;
        }

        $tx = Sep31Transaction::where($query)->first();
        if ($tx !== null) {
            return self::sep31TransactionResponseFromTx($tx);
        }
        return null;
    }

    /**
     * @throws AnchorFailure
     */
    private static function sep31TransactionResponseFromTx(Sep31Transaction $tx) : Sep31TransactionResponse {

        try {
            $amountInAsset = $tx->amount_in_asset !== null ? IdentificationFormatAsset::fromString($tx->amount_in_asset) : null;
            $amountOutAsset = $tx->amount_out_asset !== null ? IdentificationFormatAsset::fromString($tx->amount_out_asset) : null;
        } catch (InvalidAsset) {
            throw new AnchorFailure('Invalid asset in DB', 500);
        }

        // todo: check if this can not be optional...
        if ($tx->fee_details === null) {
            throw new AnchorFailure('Invalid asset in DB', 500);
        }

        $response =  new Sep31TransactionResponse(
            id: $tx->id,
            status: $tx->status,
            feeDetails: self::parseFeeDetails($tx->fee_details),
            statusEta: $tx->status_eta,
            statusMessage: $tx->message,
            amountIn: $tx->amount_in === null ? null : strval($tx->amount_in),
            amountInAsset: $amountInAsset,
            amountOut: $tx->amount_out === null ? null : strval($tx->amount_out),
            amountOutAsset: $amountOutAsset,
            quoteId: $tx->quote_id,
            stellarAccountId: $tx->stellar_account_id,
            stellarMemoType: $tx->stellar_memo_typ,
            stellarMemo: $tx->stellar_memo,
            startedAt: DateTime::createFromFormat(DATE_ATOM, $tx->tx_started_at),
            updatedAt: $tx->tx_updated_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
            completedAt: $tx->tx_completed_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
            stellarTransactionId: $tx->stellar_transaction_id,
            externalTransactionId: $tx->external_transaction_id,
            requiredInfoMessage: $tx->required_info_message,
        );

        // todo: parse methods are duplicated with sep-06 -> extract

        // refunds
        if ($tx->refunds != null) {
            $response->refunds = self::parseRefunds($tx->refunds);
        }

        return $response;

    }

    /**
     * @throws InvalidAsset
     */
    private static function sep31AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset): Sep31AssetInfo {
        try {
            $formattedAsset = new IdentificationFormatAsset
            (
                $anchorAsset->schema,
                $anchorAsset->code,
                $anchorAsset->issuer,
            );

            if ($anchorAsset->sep31_info === null) {
                throw new InvalidAsset('missing sep31_info');
            }

            $infoJson = '{"receive" : { "' . $anchorAsset->code . '":' . $anchorAsset->sep31_info . '}}';
            $info = SEP31InfoResponse::fromJson(json_decode($infoJson, true));
            $assetInfo = $info->receiveAssets[$anchorAsset->code];
            $sep12Info = $assetInfo->sep12Info;
            /**
             * @var array<Sep12Type> $senderTypes
             */
            $senderTypes = [];
            foreach ($sep12Info->senderTypes as $key => $value) {
                $senderTypes[] = new Sep12Type(name:$key, description: $value);
            }

            /**
             * @var array<Sep12Type> $receiverTypes
             */
            $receiverTypes = [];
            foreach ($sep12Info->receiverTypes as $key => $value) {
                $receiverTypes[] = new Sep12Type(name:$key, description: $value);
            }

            return new Sep31AssetInfo(
                asset: $formattedAsset,
                sep12SenderTypes: $senderTypes,
                sep12ReceiverTypes: $receiverTypes,
                minAmount: $anchorAsset->send_min_amount,
                maxAmount: $anchorAsset->send_max_amount,
                feeFixed: $anchorAsset->send_fee_fixed,
                feePercent: $anchorAsset->send_fee_percent,
                quotesSupported: $assetInfo->quotesSupported,
                quotesRequired: $assetInfo->quotesRequired,
            );
        } catch (Throwable $t) {
            throw new InvalidAsset($t->getMessage());
        }
    }

    /**
     * Extracts type and value as strings from a memo to be saved in the db
     * @param Memo $memo the memo to extract the values from
     * @return array<string,?string> keys: memo_type and memo_value
     */
    private static function memoFieldsFromMemo(Memo $memo) : array {
        $memoType = MemoHelper::memoTypeAsString($memo->getType());
        $memoValue = null;
        if ($memoType === 'hash' || $memoType === 'return') {
            $memoValue = base64_encode($memo->getValue());
        } else if ($memo->getValue() !== null) {
            $memoValue = strval($memo->getValue());
        }

        return [
            'memo_type' => MemoHelper::memoTypeAsString($memo->getType()),
            'memo_value' =>$memoValue
        ];
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
    private static function parseFeeDetails(string $feeDetailsJson) : ?TransactionFeeInfo {
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
    private static function parseRefunds(string $refundsJson) : ?TransactionRefunds {
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
}
