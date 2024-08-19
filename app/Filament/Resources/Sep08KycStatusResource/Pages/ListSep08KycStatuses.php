<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep08KycStatusResource\Pages;

use App\Filament\Resources\Sep08KycStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the SEP-08 KYC status records from the database.
 */
class ListSep08KycStatuses extends ListRecords
{
    protected static string $resource = Sep08KycStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
