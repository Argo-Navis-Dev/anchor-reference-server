<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep31CrossBorder;

use App\Models\AnchorAsset;
use App\Models\Sep31Transaction;
use App\Stellar\Sep38Quote\Sep38Helper;
use App\Stellar\Shared\SepHelper;
use ArgoNavis\PhpAnchorSdk\callback\Sep31PostTransactionRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep31TransactionResponse;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\exception\QuoteNotFoundForId;
use ArgoNavis\PhpAnchorSdk\exception\Sep31TransactionNotFoundForId;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\callback\Sep31PutTransactionCallbackRequest;
use ArgoNavis\PhpAnchorSdk\shared\Sep12Type;
use ArgoNavis\PhpAnchorSdk\shared\Sep31AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep31TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\util\MemoHelper;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31InfoResponse;
use Throwable;

use function json_encode;

class Sep31Helper
{
    /**
     * Retrieves the assets having sep31 support enabled.
     *
     * @return array<Sep31AssetInfo> the assets having sep31 support enabled.
     */
    public static function getSupportedAssets(?string $lang): array
    {
        /**
         * @var array<Sep31AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep31Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            return $result;
        }
        foreach ($assets as $asset) {
            try {
                $result[] = self::sep31AssetInfoFromAnchorAsset($asset, $lang);
            } catch (InvalidAsset $iA) {
                Log::error(
                    'Invalid asset in DB',
                    ['context' => 'sep31', 'asset' => json_encode($asset),
                        'error' => $iA->getMessage(), 'exception' => $iA],
                );
            }
        }
        Log::debug(
            'The supported assets',
            ['context' => 'sep31', 'assets' => json_encode($result)],
        );

        return $result;
    }

    /**
     * Creates and stores a new transaction from the given request data.
     *
     * @param Sep31PostTransactionRequest $request the request data.
     * @return Sep31Transaction the new created transaction.
     *
     * @throws AnchorFailure if any error occurs.
     */
    public static function newTransaction(Sep31PostTransactionRequest $request) : Sep31Transaction
    {
        Log::debug(
            'Creating new transaction.',
            ['context' => 'sep31', 'operation' => 'new_transaction', 'request' => json_encode($request)],
        );

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
            Log::debug(
                'Retrieving the quote.',
                ['context' => 'sep31', 'operation' => 'new_transaction', 'quote_id' => $request->quoteId],
            );

            try {
                $quote = Sep38Helper::getQuoteById($request->quoteId, $request->accountId, $request->accountMemo);
                $sep31Transaction->fee_details = json_encode($quote->fee->toJson());
                $sep31Transaction->amount_out = $quote->buyAmount;
            } catch (QuoteNotFoundForId | AnchorFailure $e) {
                Log::debug(
                    'Failed to get the quote.',
                    ['context' => 'sep31', 'operation' => 'new_transaction',
                        'error' => $e->getMessage(), 'exception' => $e,
                    ],
                );
                throw new AnchorFailure(
                    message: $e->getMessage(),
                    code: $e->getCode(),
                    messageKey: $e->getMessageKey(),
                    messageParams: $e->getMessageParams(),
                );

            } catch (Throwable $th) {
                Log::debug(
                    'Failed to get the quote.',
                    ['context' => 'sep31', 'operation' => 'new_transaction',
                        'error' => $th->getMessage(), 'exception' => $th,
                    ],
                );

                throw new AnchorFailure(
                    message: $th->getMessage(),
                    messageKey: 'shared_lang.error.anchor_failure',
                );
            }
        } else {
            Log::debug(
                'No quote id provided, calculating the fee.',
                ['context' => 'sep31', 'operation' => 'new_transaction'],
            );

            //Improve here the fee calculation according your business logic.
            $fee = 0.1;
            $feeDetail = 'Service fee';
            $feeAsset = $request->destinationAsset;
            if ($request->asset->feeFixed !== null) {
                $fee = $request->asset->feeFixed;
                $feeDetail = 'Fee fixed';
                $feeAsset = $request->asset->asset;
            } elseif ($request->asset->feePercent !== null) {
                $fee = $request->amount * $request->asset->feePercent;
                $feeDetail = 'Fee percent';
                $feeAsset = $request->asset->asset;
            }
            $feeInfo = new TransactionFeeInfo(
                total: strval($fee),
                asset: $feeAsset,
                details: [new TransactionFeeInfoDetail(
                    name: $feeDetail,
                    amount: strval($fee)
                )]
            );
            $sep31Transaction->fee_details = json_encode($feeInfo->toJson());
        }

        $sep31Transaction->stellar_memo = strval(rand(5000000, 150000000));
        $sep31Transaction->stellar_memo_type = 'id';

        if ($request->asset->asset->getCode() === config('stellar.assets.usdc_asset_code')) {
            $sep31Transaction->stellar_account_id = config('stellar.assets.usdc_asset_distribution_account_id');
        } elseif ($request->asset->asset->getCode() === config('stellar.assets.jpyc_asset_code')) {
            $sep31Transaction->stellar_account_id = config('stellar.assets.jpyc_asset_distribution_account_id');
        }

        $sep31Transaction->save();
        $sep31Transaction->refresh();

        Log::debug(
            'The transaction has been saved successfully.',
            ['context' => 'sep31', 'operation' => 'new_transaction', 'transaction' => json_encode($sep31Transaction)],
        );
//        Call the status change callback (optional).
//        $signingSeed = config('stellar.server.server_account_signing_key');
//        $sep31TransactionBody = self::sep31TransactionResponseFromTx($sep31Transaction);
//        CallbackHelper::setLogger(Log::getLogger());
//        CallbackHelper::sendCallbackRequest(
//            $sep31TransactionBody,
//            $signingSeed,
//            $sep31Transaction->callback_url,
//        );

        return $sep31Transaction;
    }

    /**
     * Retrives the transaction with the given id and account id from the database
     * and returns it as a Sep31TransactionResponse (JSON).
     *
     * @param string $id the transaction id.
     * @param string $accountId the account id.
     * @param string|null $accountMemo the account memo.
     *
     * @return Sep31TransactionResponse|null the transaction response.
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
        Log::debug(
            'Retrieving transaction by query.',
            ['context' => 'sep31', 'query' => json_encode($query)],
        );
        $tx = Sep31Transaction::where($query)->first();
        if ($tx !== null) {
            return self::sep31TransactionResponseFromTx($tx);
        } else {
            Log::debug('Transaction not found.', ['context' => 'sep31']);

            throw new Sep31TransactionNotFoundForId($id);
        }
    }

    /**
     * Updates the transaction by the given id and account id with a callback URL.
     *
     * @param Sep31PutTransactionCallbackRequest $request the request data.
     */
    public static function putTransactionCallback(
        Sep31PutTransactionCallbackRequest $request
    ) : void {
        Log::debug('Saving transaction callback.', ['context' => 'sep31', 'request' => json_encode($request)]);

        $query = ['id' => $request->transactionId, 'sep10_account' => $request->accountId];
        if ($request->accountMemo !== null) {
            $query['sep10_account_memo'] = $request->accountMemo;
        }
        $tx = Sep31Transaction::where($query)->first();
        if ($tx !== null) {
            $tx->callback_url = $request->url;
            $tx->save();
            $tx->refresh();
            Log::debug(
                'Transaction callback has been saved successfully.',
                ['context' => 'sep31', 'callback_url' => $tx->callback_url],
            );
        } else {
            Log::debug('Transaction not found.', ['context' => 'sep31']);

            throw new Sep31TransactionNotFoundForId($request->transactionId);
        }
    }

    /**
     * Converts the DB model to a Sep31TransactionResponse.
     *
     * @param Sep31Transaction $tx the transaction to convert (DB model).
     *
     * @return Sep31TransactionResponse the converted transaction.
     * @throws AnchorFailure
     */
    private static function sep31TransactionResponseFromTx(Sep31Transaction $tx) : Sep31TransactionResponse
    {

        try {
            $amountInAsset = $tx->amount_in_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_in_asset) : null;
            $amountOutAsset = $tx->amount_out_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_out_asset) : null;
        } catch (InvalidAsset $ex) {
            Log::debug(
                'Invalid asset in db.',
                ['context' => 'sep31', 'error' => $ex->getMessage(), 'exception' => $ex,
                    'amount_in_asset' => $tx->amount_in_asset, 'amount_out_asset' => $tx->amount_out_asset,
                    'http_status_code' => 500,
                ],
            );

            throw new AnchorFailure('Invalid asset in DB', 500);
        }

        // todo: check if this can not be optional... (leave it as it is)
        if ($tx->fee_details === null) {
            Log::debug(
                'Invalid transaction in in db, fee details field is missing.',
                ['context' => 'sep31'],
            );

            throw new AnchorFailure('Invalid transaction in DB, missing fee_details', 500);
        }

        $response =  new Sep31TransactionResponse(
            id: $tx->id,
            status: $tx->status,
            feeDetails: SepHelper::parseFeeDetails($tx->fee_details),
            statusEta: $tx->status_eta,
            statusMessage: $tx->message,
            amountIn: $tx->amount_in === null ? null : strval($tx->amount_in),
            amountInAsset: $amountInAsset,
            amountOut: $tx->amount_out === null ? null : strval($tx->amount_out),
            amountOutAsset: $amountOutAsset,
            quoteId: $tx->quote_id,
            stellarAccountId: $tx->stellar_account_id,
            stellarMemoType: $tx->stellar_memo_type,
            stellarMemo: $tx->stellar_memo,
            startedAt: DateTime::createFromFormat(DATE_ATOM, $tx->tx_started_at),
            updatedAt: $tx->tx_updated_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
            completedAt: $tx->tx_completed_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
            stellarTransactionId: $tx->stellar_transaction_id,
            externalTransactionId: $tx->external_transaction_id,
            requiredInfoMessage: $tx->required_info_message,
        );
        if ($tx->refunds != null) {
            $response->refunds = SepHelper::parseRefunds($tx->refunds);
        }

        Log::debug(
            'The built transaction model.',
            ['context' => 'sep31', 'transaction' => json_encode($response)],
        );

        return $response;
    }

    /**
     * Creates a Sep31AssetInfo from the passed AnchorAsset DB model.
     *
     * @param AnchorAsset $anchorAsset the anchor asset to convert.
     *
     * @return Sep31AssetInfo the converted asset.
     * @throws InvalidAsset
     */
    public static function sep31AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset, ?string $lang = 'en'): Sep31AssetInfo
    {
        try {
            $formattedAsset = new IdentificationFormatAsset(
                $anchorAsset->schema,
                $anchorAsset->code,
                $anchorAsset->issuer,
            );

            if ($anchorAsset->sep31_info === null) {
                Log::error(
                    'Invalid Anchor asset, sep31_info field is missing.',
                    ['context' => 'sep31', 'asset' => json_encode($anchorAsset)],
                );

                throw new InvalidAsset(
                    message: 'missing sep31_info',
                    messageKey: 'sep31_lang.error.asset.sep_31_info_missing',
                    messageParams: ['asset' => $anchorAsset->code],
                );
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
                $localizedDescription = SepHelper::localizeAssetSep12SenderReceiverDescription(
                    typeKey: $key,
                    assetCode: $anchorAsset->code,
                    defaultDescription: $value,
                    isSender: true,
                    lang: $lang,
                );
                $senderTypes[] = new Sep12Type(name:$key, description: $localizedDescription);
            }

            /**
             * @var array<Sep12Type> $receiverTypes
             */
            $receiverTypes = [];
            foreach ($sep12Info->receiverTypes as $key => $value) {
                $localizedDescription = SepHelper::localizeAssetSep12SenderReceiverDescription(
                    typeKey: $key,
                    assetCode: $anchorAsset->code,
                    defaultDescription: $value,
                    isSender: false,
                    lang: $lang,
                );
                $receiverTypes[] = new Sep12Type(name:$key, description: $localizedDescription);
            }

            $sep31AssetInfo = new Sep31AssetInfo(
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
            Log::debug(
                'The built SEP-31 asset info model.',
                ['context' => 'sep31', 'model' => json_encode($sep31AssetInfo)],
            );

            return $sep31AssetInfo;
        } catch (Throwable $t) {
            Log::error(
                'Failed to convert Anchor asset to SEP-31 asset info model.',
                ['context' => 'sep31', 'error' => $t->getMessage(), 'exception' => $t],
            );

            throw new InvalidAsset(
                message: $t->getMessage(),
                messageKey: 'asset_lang.error.invalid_asset',
            );
        }
    }

    /**
     * Extracts type and value as strings from a memo to be saved in the db
     *
     * @param Memo $memo the memo to extract the values from
     *
     * @return array<string,?string> keys: memo_type and memo_value
     */
    private static function memoFieldsFromMemo(Memo $memo) : array
    {
        $memoType = MemoHelper::memoTypeAsString($memo->getType());
        $memoValue = null;
        if ($memoType === 'hash' || $memoType === 'return') {
            $memoValue = base64_encode($memo->getValue());
        } elseif ($memo->getValue() !== null) {
            $memoValue = strval($memo->getValue());
        }

        return [
            'memo_type' => MemoHelper::memoTypeAsString($memo->getType()),
            'memo_value' =>$memoValue
        ];
    }
}
