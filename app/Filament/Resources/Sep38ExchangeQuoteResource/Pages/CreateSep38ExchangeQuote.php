<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep38ExchangeQuoteResource\Pages;

use App\Filament\Resources\Sep38ExchangeQuoteResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * This class is responsible for creating SEP-38 transaction record in the database.
 */
class CreateSep38ExchangeQuote extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = Sep38ExchangeQuoteResource::class;
}
