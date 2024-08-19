<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep38RateResource\Pages;

use App\Filament\Resources\Sep38RateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 *  This class is responsible for editing SEP-38 rate record in the database.
 */
class EditSep38Rate extends EditRecord
{
    protected static string $resource = Sep38RateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
