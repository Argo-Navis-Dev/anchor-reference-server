<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep06Transfer;

use ArgoNavis\PhpAnchorSdk\callback\ITransferIntegration;
use ArgoNavis\PhpAnchorSdk\callback\Sep06TransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\StartDepositExchangeRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartDepositRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartDepositResponse;
use ArgoNavis\PhpAnchorSdk\callback\StartWithdrawExchangeRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartWithdrawRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartWithdrawResponse;
use ArgoNavis\PhpAnchorSdk\callback\TransactionHistoryRequest;

class TransferIntegration implements ITransferIntegration
{

    /**
     * @inheritDoc
     */
    public function supportedAssets(): array
    {
        return Sep06Helper::getSupportedAssets();
    }

    /**
     * @inheritDoc
     */
    public function deposit(StartDepositRequest $request): StartDepositResponse
    {
        $sep06Transaction = Sep06Helper::newDepositTransaction($request);
        return new StartDepositResponse(
            id: $sep06Transaction->id,
            how: 'Check the transaction for more information about how to deposit.',
        );
    }

    /**
     * @inheritDoc
     */
    public function depositExchange(StartDepositExchangeRequest $request): StartDepositResponse
    {
        $sep06Transaction = Sep06Helper::newDepositExchangeTransaction($request);
        return new StartDepositResponse(
            id: $sep06Transaction->id,
            how: 'Check the transaction for more information about how to deposit.',
        );
    }

    /**
     * @inheritDoc
     */
    public function withdraw(StartWithdrawRequest $request): StartWithdrawResponse
    {
        $sep06Transaction = Sep06Helper::newWithdrawTransaction($request);
        return new StartWithdrawResponse(
            id: $sep06Transaction->id,
            accountId: $sep06Transaction->withdraw_anchor_account,
            memoType: $sep06Transaction->memo_type,
            memo: $sep06Transaction->memo,
        );
    }

    /**
     * @inheritDoc
     */
    public function withdrawExchange(StartWithdrawExchangeRequest $request): StartWithdrawResponse
    {
        $sep06Transaction = Sep06Helper::newWithdrawExchangeTransaction($request);
        return new StartWithdrawResponse(
            id: $sep06Transaction->id,
            accountId: $sep06Transaction->withdraw_anchor_account,
            memoType: $sep06Transaction->memo_type,
            memo: $sep06Transaction->memo,
        );
    }

    /**
     * @inheritDoc
     */
    public function findTransactionById(
        string $id,
        string $accountId,
        ?string $accountMemo = null,
        ?string $lang = null,
    ): ?Sep06TransactionResponse {
        return Sep06Helper::getTransaction(
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
    ): ?Sep06TransactionResponse {
        return Sep06Helper::getTransaction(
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
    ): ?Sep06TransactionResponse {
        return Sep06Helper::getTransaction(
            accountId: $accountId,
            memo: $accountMemo,
            externalTxId: $externalTransactionId,
        );
    }

    /**
     * @inheritDoc
     */
    public function getTransactionHistory(
        TransactionHistoryRequest $request,
        string $accountId,
        ?string $accountMemo = null,
    ): ?array {
        return Sep06Helper::getTransactionHistory($request, $accountId, $accountMemo);
    }
}
