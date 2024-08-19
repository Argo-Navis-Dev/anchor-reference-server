<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12TypeToFieldsResource\Pages;

use App\Filament\Resources\Sep12TypeToFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the SEP-12 customer type to field records from the database.
 */
class ListSep12TypeToFields extends ListRecords
{
    protected static string $resource = Sep12TypeToFieldsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
