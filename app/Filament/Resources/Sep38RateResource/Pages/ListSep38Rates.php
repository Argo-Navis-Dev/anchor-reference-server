<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep38RateResource\Pages;

use App\Filament\Resources\Sep38RateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the SEP-38 rate records from the database.
 */
class ListSep38Rates extends ListRecords
{
    protected static string $resource = Sep38RateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
