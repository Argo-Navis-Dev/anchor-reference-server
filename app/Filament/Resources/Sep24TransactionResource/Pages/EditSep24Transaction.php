<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep24TransactionResource\Pages;

use App\Filament\Resources\Sep06And24ResourceUtil;
use App\Filament\Resources\Sep24TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 *  This class is responsible for editing SEP-24 transaction record in the database.
 */
class EditSep24Transaction extends EditRecord
{
    protected static string $resource = Sep24TransactionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $model = $this->getRecord();
        Sep06And24ResourceUtil::populateDataBeforeFormLoad($data, $model);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
