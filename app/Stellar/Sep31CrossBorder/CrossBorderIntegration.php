<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep31CrossBorder;

use ArgoNavis\PhpAnchorSdk\callback\ICrossBorderIntegration;
use ArgoNavis\PhpAnchorSdk\callback\Sep31PostTransactionRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep31PostTransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\Sep31PutTransactionCallbackRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep31TransactionResponse;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;

class CrossBorderIntegration implements ICrossBorderIntegration
{

    public function supportedAssets(string $accountId, ?string $accountMemo = null, ?string $lang = null): array
    {
        return Sep31Helper::getSupportedAssets();
    }

    public function postTransaction(Sep31PostTransactionRequest $request): Sep31PostTransactionResponse
    {
        $sep31Transaction = Sep31Helper::newTransaction($request);
        return new Sep31PostTransactionResponse(
            id: $sep31Transaction->id,
            stellarAccountId: $sep31Transaction->stellar_account_id,
            stellarMemoType: $sep31Transaction->stellar_memo_typ,
            stellarMemo: $sep31Transaction->stellar_memo,
        );
    }

    public function getTransactionById(string $id, string $accountId, ?string $accountMemo = null,): Sep31TransactionResponse
    {
        return Sep31Helper::getTransaction(id: $id, accountId: $accountId, accountMemo: $accountMemo);
    }

    public function putTransactionCallback(Sep31PutTransactionCallbackRequest $request): void
    {
        throw new AnchorFailure('not yet implemented');
    }
}
