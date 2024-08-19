<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep31TransactionResource\Pages;

use App\Filament\Resources\Sep31TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep31Transactions extends ListRecords
{
    protected static string $resource = Sep31TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
