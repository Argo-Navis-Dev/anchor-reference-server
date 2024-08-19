<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep38ExchangeQuoteResource\Pages;

use App\Filament\Resources\Sep38ExchangeQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the SEP-38 transaction exchange quote records from the database.
 */
class ListSep38ExchangeQuotes extends ListRecords
{
    protected static string $resource = Sep38ExchangeQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
