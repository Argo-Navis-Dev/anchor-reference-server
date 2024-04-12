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
use ArgoNavis\PhpAnchorSdk\callback\Sep24TransactionHistoryRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep24TransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\Sep24WithdrawTransactionResponse;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\DepositOperation;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep24AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep24TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\WithdrawOperation;
use ArgoNavis\PhpAnchorSdk\util\MemoHelper;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Memo;

class Sep24Helper
{

    /**
     * @return array<Sep24AssetInfo> the assets having sep24 support enabled.
     */
    public static function getSupportedAssets(): array {
        /**
         * @var array<Sep24AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep24Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            return $result;
        }
        foreach ($assets as $asset) {
            try {
                $result[] = self::sep24AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error('invalid anchor_asset (id: '. $asset->id . ') in db: ' . $iA->getMessage());
            }
        }

        return $result;
    }

    public static function getAssetInfo(string $assetCode, ?string $assetIssuer = null) : ?Sep24AssetInfo {
        $query = ['sep24_enabled' => true, 'code' => $assetCode];
        if ($assetIssuer !== null) {
            $query['issuer'] = $assetIssuer;
        }

        $asset = AnchorAsset::where($query)->first();
        if ($asset != null) {
            try {
                return self::sep24AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error('invalid anchor_asset (id: '. $asset->id . ') in db: ' . $iA->getMessage());
            }
        }
        return null;
    }

    public static function getTransaction(
        string $accountId,
        ?string $memo = null,
        ?string $id = null,
        ?string $stellarTxId = null,
        ?string $externalTxId = null) : ?Sep24TransactionResponse {

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
        $tx = Sep24Transaction::where($query)->first();
        if ($tx !== null) {
            $result = self::sep24TransactionResponseFromTx($tx);
            $customer = Sep12Helper::getSep12CustomerByAccountId($accountId, $memo);
            if($customer !== null && $customer->status === CustomerStatus::ACCEPTED) {
                $result->kycVerified = true;
            } else {
                $result->kycVerified = false;
            }
            return $result;
        }
        return null;
    }

    public static function getTransactionHistory(
        Sep24TransactionHistoryRequest $request,
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
            $txsQueryBuilder = Sep24Transaction::where(function ($query) use ($dateStr) {
                $query->where('tx_started_at', '>=', $dateStr);
            })->where($baseQuery);
        }

        $txsQueryBuilder = $txsQueryBuilder->orderBy('tx_started_at');

        if ($request->limit != null) {
            $txsQueryBuilder = $txsQueryBuilder->limit($request->limit);
        }

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

            return $results;
        }

        return null;
    }

    public static function newTransaction(InteractiveWithdrawRequest | InteractiveDepositRequest $request) :Sep24Transaction {

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

        if ($request instanceof InteractiveDepositRequest) {
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
        return $sep24Transaction;
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
     * @throws InvalidAsset
     */
    private static function sep24AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset): Sep24AssetInfo {
        $formattedAsset = new IdentificationFormatAsset
        (
            $anchorAsset->schema,
            $anchorAsset->code,
            $anchorAsset->issuer,
        );
        $depositOp = new DepositOperation
        (
            (bool)$anchorAsset->deposit_enabled,
            $anchorAsset->deposit_min_amount,
            $anchorAsset->deposit_max_amount,
            $anchorAsset->deposit_fee_fixed,
            $anchorAsset->deposit_fee_percent,
            $anchorAsset->deposit_fee_minimum,
        );
        $withdrawOp = new WithdrawOperation
        (
            (bool)$anchorAsset->withdrawal_enabled,
            $anchorAsset->withdrawal_min_amount,
            $anchorAsset->withdrawal_max_amount,
            $anchorAsset->withdrawal_fee_fixed,
            $anchorAsset->withdrawal_fee_percent,
            $anchorAsset->withdrawal_fee_minimum,
        );

        return new Sep24AssetInfo($formattedAsset, $depositOp, $withdrawOp);
    }

    private static function sep24TransactionResponseFromTx(Sep24Transaction $tx) : Sep24TransactionResponse {

        if ($tx->kind === 'deposit') {

            return new Sep24DepositTransactionResponse(
                id:$tx->id,
                status: $tx->status,
                startedAt: DateTime::createFromFormat(DATE_ATOM, $tx->tx_started_at),
                from:$tx->from_account,
                to: $tx->to_account,
                amountIn: $tx->amount_in,
                amountOut: $tx->amount_out,
                amountFee: $tx->amount_fee,
                moreInfoUrl: $tx->more_info_url,
                stellarTransactionId: $tx->stellar_transaction_id,
                claimableBalanceId: $tx->claimable_balance_id,
                depositMemo: $tx->memo,
                depositMemoType:$tx->memo_type,
                statusEta: $tx->status_eta,
                amountInAsset: $tx->amount_in_asset,
                amountOutAsset: $tx->amount_out_asset,
                amountFeeAsset: $tx->amount_fee_asset,
                quoteId: $tx->quote_id,
                completedAt: $tx->tx_completed_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
                updatedAt: $tx->tx_updated_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
                externalTransactionId: $tx->external_transaction_id,
                message: $tx->status_message,
                refunded: boolval($tx->refunded)
            );
            // TODO: add refunds
        } else {
            return new Sep24WithdrawTransactionResponse(
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
                amountInAsset: $tx->amount_in_asset,
                amountOutAsset: $tx->amount_out_asset,
                amountFeeAsset: $tx->amount_fee_asset,
                quoteId: $tx->quote_id,
                completedAt: $tx->tx_completed_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
                updatedAt: $tx->tx_updated_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
                externalTransactionId: $tx->external_transaction_id,
                message: $tx->status_message,
                refunded: boolval($tx->refunded)
            );
            // TODO: add refunds
        }
    }

    /**
     * @throws AnchorFailure
     */
    public static function createOrUpdateCustomer(InteractiveDepositRequest | InteractiveWithdrawRequest $request) : void {

        if (!$request->hasKycData()) {
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
            throw new AnchorFailure('could not extract account from jwt token');
        }

        // create or update with the given data.
        $putCustomerRequest = new PutCustomerRequest(
            account: $accountId,
            memo: $accountMemo,
            kycFields: $request->kycFields,
            kycUploadedFiles: $request->kycUploadedFiles);
        $customerIntegration = new CustomerIntegration();
        $customerIntegration->putCustomer($putCustomerRequest);

    }

    public static function createInteractivePopupUrl(string $txId): string {
        // This is just a placeholder implementation
        // As soon as we have a dem it needs to be changed
        // The token needs to be replaced with a new short-lived jwt token.
        $newJwtToken = 'placeholderToken';
        return 'https://localhost:5173/interactive-popup?tx=' . $txId . '&token=' . $newJwtToken;
    }
}
