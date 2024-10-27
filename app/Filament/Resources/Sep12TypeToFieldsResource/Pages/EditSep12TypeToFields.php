<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12TypeToFieldsResource\Pages;

use App\Filament\Resources\ResourceUtil;
use App\Filament\Resources\Sep12TypeToFieldsResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

use function json_encode;

/**
 *  This class is responsible for editing SEP-12 customer type to field record in the database.
 */
class EditSep12TypeToFields extends EditRecord
{
    /**
     * @var string $resource The db entity to be edited.
     */
    protected static string $resource = Sep12TypeToFieldsResource::class;

    /**
     * Preprocesses the form data model before saving in the database.
     *
     * @param array<array-key, mixed> $data The form data model to be saved.
     *
     * @return array<array-key, mixed> $data The processed form data model.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::debug(
            'Preparing data for save action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );
        $data['required_fields'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'required_fields');
        $data['optional_fields'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'optional_fields');
        Log::debug(
            'The processed data for save action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );

        return $data;
    }

    /**
     * Processes the form data model before filling it.
     *
     * @param array<array-key, mixed> $data The form data model.
     *
     * @return array<array-key, mixed> $data The processed form data model.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        Log::debug(
            'Preparing form data for edit action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );

        $requiredFieldsStr = $data['required_fields'] ?? null;
        if ($requiredFieldsStr != null) {
            $requiredFields = array_map('trim', explode(',', $requiredFieldsStr));
            $data['required_fields'] = $requiredFields;
        }

        $optionalFieldsStr = $data['optional_fields'] ?? null;
        if ($optionalFieldsStr != null) {
            $optionalFields = array_map('trim', explode(',', $optionalFieldsStr));
            $data['optional_fields'] = $optionalFields;
        }
        Log::debug(
            'The processed form data for edit action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
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
