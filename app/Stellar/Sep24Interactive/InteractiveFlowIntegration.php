<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep24Interactive;

use App\Stellar\Sep38Quote\Sep38Helper;
use ArgoNavis\PhpAnchorSdk\callback\IInteractiveFlowIntegration;
use ArgoNavis\PhpAnchorSdk\callback\InteractiveDepositRequest;
use ArgoNavis\PhpAnchorSdk\callback\InteractiveTransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\InteractiveWithdrawRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep24TransactionHistoryRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep24TransactionResponse;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\QuoteNotFoundForId;
use ArgoNavis\PhpAnchorSdk\shared\Sep24AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep38Quote;

class InteractiveFlowIntegration implements IInteractiveFlowIntegration
{

    /**
     * @inheritDoc
     */
    public function supportedAssets(): array
    {
        return Sep24Helper::getSupportedAssets();
    }

    /**
     * @inheritDoc
     */
    public function getAsset(string $code, ?string $issuer = null): ?Sep24AssetInfo
    {
        return Sep24Helper::getAssetInfo($code, $issuer);
    }

    /**
     * @inheritDoc
     */
    public function getFee(string $operation, string $assetCode, float $amount, ?string $type = null): float
    {
        throw new AnchorFailure('fee endpoint is not supported');
    }

    /**
     * @inheritDoc
     */
    public function withdraw(InteractiveWithdrawRequest $request): InteractiveTransactionResponse
    {
        if ($request->hasKycData()) {
            Sep24Helper::createOrUpdateCustomer($request);
        }

        $sep24Transaction = Sep24Helper::newTransaction($request);

        return new InteractiveTransactionResponse(
            type: 'interactive_customer_info_needed',
            url: Sep24Helper::createInteractivePopupUrl($sep24Transaction->id),
            id: $sep24Transaction->id);
    }

    /**
     * @inheritDoc
     */
    public function deposit(InteractiveDepositRequest $request): InteractiveTransactionResponse
    {
        if ($request->hasKycData()) {
            Sep24Helper::createOrUpdateCustomer($request);
        }

        $sep24Transaction = Sep24Helper::newTransaction($request);

        return new InteractiveTransactionResponse(
            type: 'interactive_customer_info_needed',
            url: Sep24Helper::createInteractivePopupUrl($sep24Transaction->id),
            id: $sep24Transaction->id);
    }

    /**
     * @inheritDoc
     */
    public function findTransactionById(
        string $id,
        string $accountId,
        ?string $accountMemo = null,
        ?string $lang = null,
    ): ?Sep24TransactionResponse
    {
        return Sep24Helper::getTransaction(
            accountId: $accountId,
            memo: $accountMemo,
            id: $id,
        );
    }

    /**
     * @inheritDoc
     */
    public function findTransactionByStellarTransactionId(
        string $stellarTransactionId,
        string $accountId,
        ?string $accountMemo = null,
        ?string $lang = null,
    ): ?Sep24TransactionResponse
    {
        return Sep24Helper::getTransaction(
            accountId: $accountId,
            memo: $accountMemo,
            stellarTxId: $stellarTransactionId,
        );
    }

    /**
     * @inheritDoc
     */
    public function findTransactionByExternalTransactionId(
        string $externalTransactionId,
        string $accountId,
        ?string $accountMemo = null,
        ?string $lang = null,
    ): ?Sep24TransactionResponse
    {
        return Sep24Helper::getTransaction(
            accountId: $accountId,
            memo: $accountMemo,
            externalTxId: $externalTransactionId,
        );
    }

    /**
     * @inheritDoc
     */
    public function getTransactionHistory(
        Sep24TransactionHistoryRequest $request,
        string $accountId,
        ?string $accountMemo = null,
    ): ?array
    {
        return Sep24Helper::getTransactionHistory($request, $accountId, $accountMemo);
    }

    public function getQuoteById(string $quoteId, string $accountId, ?string $accountMemo = null): Sep38Quote
    {
        return Sep38Helper::getQuoteById($quoteId, $accountId, $accountMemo);
    }
}
