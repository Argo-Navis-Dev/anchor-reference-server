<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep24Interactive;

use App\Models\AnchorAsset;
use App\Models\Sep24Transaction;
use App\Stellar\Sep12Customer\CustomerIntegration;
use App\Stellar\Sep12Customer\Sep12Helper;
use ArgoNavis\PhpAnchorSdk\callback\InteractiveDepositRequest;
use ArgoNavis\PhpAnchorSdk\callback\InteractiveWithdrawRequest;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep24DepositTransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\TransactionHistoryRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep24TransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\Sep24WithdrawTransactionResponse;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\DepositOperation;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep24AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep24TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefundPayment;
use ArgoNavis\PhpAnchorSdk\shared\TransactionRefunds;
use ArgoNavis\PhpAnchorSdk\shared\WithdrawOperation;
use ArgoNavis\PhpAnchorSdk\util\MemoHelper;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Memo;

use function json_encode;

class Sep24Helper
{

    /**
     * @return array<Sep24AssetInfo> the assets having sep24 support enabled.
     */
    public static function getSupportedAssets(): array
    {
        /**
         * @var array<Sep24AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep24Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            Log::debug(
                'Anchor asset list is empty.',
                ['context' => 'sep24'],
            );

            return $result;
        }
        foreach ($assets as $asset) {
            try {
                $result[] = self::sep24AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error(
                    'Invalid Anchor asset.',
                    ['context' => 'sep24', 'error' => $iA->getMessage(), 'exception' => $iA,
                        'id' => $asset->id, 'code' => $asset->code, 'issuer' => $asset->issuer],
                );
            }
        }

        return $result;
    }

    public static function getAssetInfo(string $assetCode, ?string $assetIssuer = null) : ?Sep24AssetInfo
    {
        $query = ['sep24_enabled' => true, 'code' => $assetCode];
        if ($assetIssuer !== null) {
            $query['issuer'] = $assetIssuer;
        }
        Log::debug(
            'Retrieving asset info from DB.',
            ['context' => 'sep24', 'operation' => 'get_asset_info', 'query' => json_encode($query)],
        );

        $asset = AnchorAsset::where($query)->first();
        if ($asset != null) {
            try {
                return self::sep24AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error(
                    'Invalid SEP-24 Anchor asset.',
                    ['context' => 'sep24', 'operation' => 'get_asset_info', 'error' => $iA->getMessage(),
                        'exception' => $iA, 'id' => $asset->id, 'code' => $asset->code, 'issuer' => $asset->issuer],
                );
            }
        }

        return null;
    }

    public static function getTransaction(
        string $accountId,
        ?string $memo = null,
        ?string $id = null,
        ?string $stellarTxId = null,
        ?string $externalTxId = null
    ) : ?Sep24TransactionResponse {

        $query = ['sep10_account' => $accountId];
        if ($id !== null) {
            $query['id'] = $id;
        } elseif ($stellarTxId !== null) {
            $query['stellar_transaction_id'] = $stellarTxId;
        } elseif ($externalTxId !== null) {
            $query['external_transaction_id'] = $externalTxId;
        }

        if ($memo !== null) {
            $query['sep10_account_memo'] = $memo;
        }
        Log::debug(
            'Retrieving transaction from DB.',
            ['context' => 'sep24', 'operation' => 'get_transaction', 'query' => json_encode($query)],
        );

        $tx = Sep24Transaction::where($query)->first();
        if ($tx !== null) {
            $result = self::sep24TransactionResponseFromTx($tx);
            $customer = Sep12Helper::getSep12CustomerByAccountId($accountId, $memo);
            if ($customer !== null && $customer->status === CustomerStatus::ACCEPTED) {
                $result->kycVerified = true;
            } else {
                $result->kycVerified = false;
            }
            Log::debug(
                'Transaction found.',
                ['context' => 'sep24', 'operation' => 'get_transaction', 'transaction' => json_encode($tx)],
            );

            return $result;
        }else {
            Log::debug(
                'Transaction not found.',
                ['context' => 'sep24', 'operation' => 'get_transaction', 'query' => json_encode($query)],
            );
        }

        return null;
    }

    public static function getTransactionHistory(
        TransactionHistoryRequest $request,
        string $accountId,
        ?string $memo = null,
    ) : ?array {

        $baseQuery = [
            'sep10_account' => $accountId,
            'request_asset_code' => $request->assetCode,
            ];

        if ($memo !== null) {
            $baseQuery['sep10_account_memo'] = $memo;
        }

        if ($request->noOlderThan != null) {
            $baseQuery['sep10_account_memo'] = $memo;
        }

        // TODO paging_id

        $txsQueryBuilder = Sep24Transaction::where($baseQuery);
        if ($request->noOlderThan != null) {
            $dateStr  = $request->noOlderThan->format(DateTimeInterface::ATOM);
            Log::debug(
                'The transaction history query date limit.',
                ['context' => 'sep24', 'operation' => 'get_transaction_history', 'date' => $dateStr],
            );

            $txsQueryBuilder = Sep24Transaction::where(function ($query) use ($dateStr) {
                $query->where('tx_started_at', '>=', $dateStr);
            })->where($baseQuery);
        }
        $txsQueryBuilder->orderBy('tx_started_at');

        if ($request->limit != null) {
            $txsQueryBuilder = $txsQueryBuilder->limit($request->limit);
        }
        Log::debug(
            'Retrieving the transaction history from db.',
            ['context' => 'sep24', 'operation' => 'get_transaction_history',
                'query' => json_encode($txsQueryBuilder->toSql())],
        );

        $txs = $txsQueryBuilder->get();
        if ($txs !== null && count($txs) > 0) {
            $results = [];
            $customer = Sep12Helper::getSep12CustomerByAccountId($accountId, $memo);
            $kycVerified = false;
            if ($customer !== null && $customer->status === CustomerStatus::ACCEPTED) {
                $kycVerified = true;
            }
            foreach ($txs as $tx) {
                $result = self::sep24TransactionResponseFromTx($tx);
                $result->kycVerified = $kycVerified;
                $results[] = $result;
            }
            Log::debug(
                'The list  of transactions.',
                ['context' => 'sep24', 'transactions' => json_encode($results)],
            );

            return $results;
        }else {
            Log::debug(
                'No transaction history found.',
                ['context' => 'sep24', 'operation' => 'get_transaction_history'],
            );
        }

        return null;
    }

    public static function newTransaction(InteractiveWithdrawRequest | InteractiveDepositRequest $request) :Sep24Transaction
    {
        $isDepositRequest = $request instanceof InteractiveDepositRequest;
        Log::debug(
            'Saving new transaction.',
            ['context' => 'sep24', 'request' => json_encode($request), 'is_deposit' => $isDepositRequest],
        );
        $sep24Transaction = new Sep24Transaction;
        $sep24Transaction->status = Sep24TransactionStatus::INCOMPLETE;
        $sep24Transaction->request_asset_code = $request->asset->getCode();
        $sep24Transaction->request_asset_issuer = $request->asset->getIssuer();
        $start = new DateTime('now');
        $sep24Transaction->tx_started_at = $start->format(DateTimeInterface::ATOM);
        if ($request->jwtToken->muxedAccountId != null) {
            $sep24Transaction->sep10_account = $request->jwtToken->muxedAccountId;
        } else {
            $sep24Transaction->sep10_account = $request->jwtToken->accountId;
            $sep24Transaction->sep10_account_memo = $request->jwtToken->accountMemo;
        }
        $sep24Transaction->client_domain = $request->jwtToken->clientDomain;
        $sep24Transaction->claimable_balance_supported = $request->claimableBalanceSupported;
        if ($request->memo !== null) {
            $memoFields = self::memoFieldsFromMemo($request->memo);
            $sep24Transaction->memo = $memoFields['memo_value'];
            $sep24Transaction->memo_type = $memoFields['memo_type'];
        }
        $sep24Transaction->amount_expected = $request->amount;

        if ($isDepositRequest) {
            $sep24Transaction->kind = 'deposit';
            $sep24Transaction->source_asset = $request->sourceAsset?->getStringRepresentation();
            $sep24Transaction->to_account = $request->account;
        } else {
            $sep24Transaction->kind = 'withdraw';
            $sep24Transaction->destination_asset = $request->destinationAsset?->getStringRepresentation();
            $sep24Transaction->from_account = $request->account;
            $assetInfo = self::getAssetInfo($request->asset->getCode(), $request->asset->getIssuer());
            $sep24Transaction->to_account = $assetInfo->asset->getIssuer();
            $sep24Transaction->withdraw_anchor_account = $assetInfo->asset->getIssuer();
        }

        $sep24Transaction->save();
        $sep24Transaction->refresh();
        Log::debug(
            'The new transaction has been saved successfully.',
            ['context' => 'sep24', 'transaction' => json_encode($sep24Transaction)],
        );

        return $sep24Transaction;
    }

    /**
     * Extracts type and value as strings from a memo to be saved in the db
     * @param Memo $memo the memo to extract the values from
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
    /**
     * @throws InvalidAsset
     */
    private static function sep24AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset): Sep24AssetInfo
    {
        $formattedAsset = new IdentificationFormatAsset(
            $anchorAsset->schema,
            $anchorAsset->code,
            $anchorAsset->issuer,
        );
        $depositOp = new DepositOperation(
            (bool)$anchorAsset->deposit_enabled,
            $anchorAsset->deposit_min_amount,
            $anchorAsset->deposit_max_amount,
            $anchorAsset->deposit_fee_fixed,
            $anchorAsset->deposit_fee_percent,
            $anchorAsset->deposit_fee_minimum,
        );
        $withdrawOp = new WithdrawOperation(
            (bool)$anchorAsset->withdrawal_enabled,
            $anchorAsset->withdrawal_min_amount,
            $anchorAsset->withdrawal_max_amount,
            $anchorAsset->withdrawal_fee_fixed,
            $anchorAsset->withdrawal_fee_percent,
            $anchorAsset->withdrawal_fee_minimum,
        );
        $sep24AssetInfo = new Sep24AssetInfo($formattedAsset, $depositOp, $withdrawOp);
        Log::debug(
            'Building SEP-24 asset info.',
            ['context' => 'sep24', 'asset_info' => json_encode($sep24AssetInfo)],
        );

        return $sep24AssetInfo;
    }

    /**
     * @throws AnchorFailure
     */
    private static function sep24TransactionResponseFromTx(Sep24Transaction $tx) : Sep24TransactionResponse
    {

        try {
            $amountInAsset = $tx->amount_in_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_in_asset) : null;
            $amountOutAsset = $tx->amount_out_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_out_asset) : null;
            $amountFeeAsset = $tx->amount_fee_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_fee_asset) : null;
        } catch (InvalidAsset $ex) {
            Log::debug(
                'Invalid asset info.',
                ['context' => 'sep24', 'error' => $ex->getMessage(), 'exception' => $ex],
            );

            throw new AnchorFailure('Invalid asset in DB', 500);
        }

        $refunds = null;
        if ($tx->refunds !== null) {
            $refunds = self::parseRefunds($tx->refunds);
        }

        if ($tx->kind === 'deposit') {
            $depositResponse = new Sep24DepositTransactionResponse(
                id:$tx->id,
                status: $tx->status,
                startedAt: DateTime::createFromFormat(DATE_ATOM, $tx->tx_started_at),
                from:$tx->from_account,
                to: $tx->to_account,
                amountIn: $tx->amount_in === null ? null : strval($tx->amount_in),
                amountOut: $tx->amount_out === null ? null : strval($tx->amount_out),
                amountFee: $tx->amount_fee === null ? null : strval($tx->amount_fee),
                moreInfoUrl: $tx->more_info_url,
                stellarTransactionId: $tx->stellar_transaction_id,
                claimableBalanceId: $tx->claimable_balance_id,
                depositMemo: $tx->memo,
                depositMemoType:$tx->memo_type,
                statusEta: $tx->status_eta,
                amountInAsset: $amountInAsset,
                amountOutAsset: $amountOutAsset,
                amountFeeAsset: $amountFeeAsset,
                quoteId: $tx->quote_id,
                completedAt: $tx->tx_completed_at === null ? null :
                    DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
                updatedAt: $tx->tx_updated_at === null ? null :
                    DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
                externalTransactionId: $tx->external_transaction_id,
                message: $tx->status_message,
                refunded: boolval($tx->refunded),
                refunds: $refunds,
            );
            Log::debug(
                'The built deposit transaction response.',
                ['context' => 'sep24', 'transaction' => json_encode($depositResponse)],
            );

            return $depositResponse;
        } else {
            $withdrawResponse = new Sep24WithdrawTransactionResponse(
                id: $tx->id,
                withdrawAnchorAccount: $tx->withdraw_anchor_account ?? '',
                status: $tx->status,
                startedAt: DateTime::createFromFormat(DATE_ATOM, $tx->tx_started_at),
                from: $tx->from_account,
                to: $tx->to_account,
                amountIn: $tx->amount_in,
                amountOut: $tx->amount_out,
                amountFee: $tx->amount_fee,
                moreInfoUrl: $tx->more_info_url,
                stellarTransactionId: $tx->stellar_transaction_id,
                withdrawMemo: $tx->memo,
                withdrawMemoType:$tx->memo_type,
                statusEta: $tx->status_eta,
                amountInAsset: $amountInAsset,
                amountOutAsset: $amountOutAsset,
                amountFeeAsset: $amountFeeAsset,
                quoteId: $tx->quote_id,
                completedAt: $tx->tx_completed_at === null ? null :
                    DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
                updatedAt: $tx->tx_updated_at === null ? null :
                    DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
                externalTransactionId: $tx->external_transaction_id,
                message: $tx->status_message,
                refunded: boolval($tx->refunded),
                refunds: $refunds,
            );
            Log::debug(
                'The built withdraw transaction response.',
                ['context' => 'sep24', 'transaction' => json_encode($withdrawResponse)],
            );

            return $withdrawResponse;
        }
    }

    /**
     * @throws AnchorFailure
     */
    public static function createOrUpdateCustomer(InteractiveDepositRequest | InteractiveWithdrawRequest $request) : void
    {

        Log::debug(
            'Creating or updating customer.',
            ['context' => 'sep24', 'operation' => 'create_or_update_customer', 'request' => json_encode($request)],
        );

        if (!$request->hasKycData()) {
            Log::warning(
                'Request does not have KYC data.',
                ['context' => 'sep24', 'operation' => 'create_or_update_customer'],
            );

            // noting to do
            return;
        }

        $jwtToken = $request->jwtToken;
        $accountId = $jwtToken->muxedAccountId;
        $accountMemo = null;
        if ($accountId === null) {
            $accountId = $jwtToken->accountId;
            $accountMemo = $jwtToken->accountMemo;
        }

        // if accountId is null the jwt token is not okay.
        if ($accountId === null) {
            Log::debug(
                'Failed to retrieve account from JWT token.',
                ['context' => 'sep24', 'operation' => 'create_or_update_customer',
                    'token' => json_encode($jwtToken),
                ],
            );

            throw new AnchorFailure('could not extract account from jwt token');
        }

        // create or update with the given data.
        $putCustomerRequest = new PutCustomerRequest(
            account: $accountId,
            memo: $accountMemo,
            kycFields: $request->kycFields,
            kycUploadedFiles: $request->kycUploadedFiles
        );
        $customerIntegration = new CustomerIntegration();
        Log::debug(
            'Saving customer.',
            ['context' => 'sep24', 'operation' => 'create_or_update_customer'],
        );

        $customerIntegration->putCustomer($putCustomerRequest);
    }

    public static function createInteractivePopupUrl(string $txId): string
    {
        // This is just a placeholder implementation
        // As soon as we have a dem it needs to be changed
        // The token needs to be replaced with a new short-lived jwt token.
        $newJwtToken = 'placeholderToken';
        $url = 'https://localhost:5173/interactive-popup?tx=' . $txId . '&token=' . $newJwtToken;
        Log::debug(
            'The interactive pop url.',
            ['context' => 'sep24', 'operation' => 'popup_url', 'url' => $url],
        );

        return $url;
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
    private static function parseRefunds(string $refundsJson) : ?TransactionRefunds
    {
        Log::debug(
            'Parsing refunds.',
            ['context' => 'sep24', 'operation' => 'parse_refunds', 'refunds' => $refundsJson],
        );

        $refunds = json_decode($refundsJson, true);

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
                    }
                }
                $refunds = new TransactionRefunds(
                    amountRefunded: $refunds['amount_refunded'],
                    amountFee: $refunds['amount_fee'],
                    payments: $payments[],
                );
                Log::debug(
                    'The parsed refunds.',
                    ['context' => 'sep24', 'operation' => 'parse_refunds', 'refunds' => json_encode($refunds)],
                );

                return $refunds;
            }
        }
        return null;
    }
}
