<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\Sep06And24ResourceUtil;
use App\Filament\Resources\Sep06TransactionResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

use function json_encode;

/**
 *  This class is responsible for editing SEP-06 transaction record.
 */
class EditSep06Transaction extends EditRecord
{
    /**
     * @var string $resource The db entity to be edited.
     */
    protected static string $resource = Sep06TransactionResource::class;

    /**
     * Processes the form data model before filling it.
     *
     * @param array<array-key, mixed> $data The form data model.
     * @return array<array-key, mixed> The processed form data model.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        Log::debug(
            'Preparing data for edit action.',
            ['context' => 'sep06_ui', 'data' => json_encode($data)],
        );
        $model = $this->getRecord();
        Sep06And24ResourceUtil::populateDataBeforeFormLoad($data, $model);
        Log::debug(
            'The processed data for edit action.',
            ['context' => 'sep06_ui', 'data' => json_encode($data)],
        );

        return $data;
    }

    /**
     * Returns the form header actions.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
