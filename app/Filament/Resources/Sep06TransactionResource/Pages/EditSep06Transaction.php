<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\Sep06And24ResourceUtil;
use App\Filament\Resources\Sep06TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 *  This class is responsible for editing SEP-06 transaction record.
 */
class EditSep06Transaction extends EditRecord
{
    protected static string $resource = Sep06TransactionResource::class;

    /**
     * Prepares the form model data before loading the form.
     *
     * @param array<array-key, mixed> $data The form model data.
     * @return array<array-key, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $model = $this->getRecord();
        Sep06And24ResourceUtil::populateDataBeforeFormLoad($data, $model);
        return $data;
    }

    /**
     * Defines the edit form header actions.
     * @return array|Actions\Action[]|Actions\ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
