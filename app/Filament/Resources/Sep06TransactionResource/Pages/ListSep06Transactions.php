<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\Sep06TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the SEP-06 transactions.
 */
class ListSep06Transactions extends ListRecords
{
    /**
     * @var string $resource The db entity to be listed.
     */
    protected static string $resource = Sep06TransactionResource::class;

    /**
     * Returns the table header actions.
     * @return array<mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
