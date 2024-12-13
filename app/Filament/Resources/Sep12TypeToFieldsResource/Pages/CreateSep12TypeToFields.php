<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12TypeToFieldsResource\Pages;

use App\Filament\Resources\ResourceUtil;
use App\Filament\Resources\Sep12TypeToFieldsResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

use function json_encode;

/**
 * This class is responsible for creating SEP-12 customer type to field record in the database.
 */
class CreateSep12TypeToFields extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = Sep12TypeToFieldsResource::class;

    /**
     * Mutates the form data before creating a resource.
     *
     * @param array<array-key, mixed> $data The form data.
     * @return array<array-key, mixed> $data The mutated form data.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::debug(
            'Preparing data for create action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );
        $data['required_fields'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'required_fields');
        $data['optional_fields'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'optional_fields');
        Log::debug(
            'The processed data for create action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );
        return $data;
    }
}
