<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep24TransactionResource\Pages;

use App\Filament\Resources\Sep24TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the SEP-24 transaction records from the database.
 */
class ListSep24Transactions extends ListRecords
{
    protected static string $resource = Sep24TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
