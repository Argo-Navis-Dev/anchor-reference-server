<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep38Quote;

use ArgoNavis\PhpAnchorSdk\callback\IQuotesIntegration;
use ArgoNavis\PhpAnchorSdk\callback\Sep38PriceRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep38PricesRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep38QuoteRequest;
use ArgoNavis\PhpAnchorSdk\shared\Sep38Price;
use ArgoNavis\PhpAnchorSdk\shared\Sep38Quote;

class QuotesIntegration implements IQuotesIntegration
{

    /**
     * @inheritDoc
     */
    public function supportedAssets(?string $accountId = null, ?string $accountMemo = null): array
    {
        return Sep38Helper::getSupportedAssets();
    }

    /**
     * @inheritDoc
     */
    public function getPrices(Sep38PricesRequest $request): array
    {
        return Sep38Helper::getPrices($request);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(Sep38PriceRequest $request): Sep38Price
    {
        return Sep38Helper::getPrice($request);
    }

    /**
     * @inheritDoc
     */
    public function getQuote(Sep38QuoteRequest $request): Sep38Quote
    {
        return Sep38Helper::addQuote($request);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteById(string $id, string $accountId, ?string $accountMemo = null): Sep38Quote
    {
        return Sep38Helper::getQuoteById($id, $accountId, $accountMemo);
    }
}
