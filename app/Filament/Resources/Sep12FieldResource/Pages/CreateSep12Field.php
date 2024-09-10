<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12FieldResource\Pages;

use App\Filament\Resources\ResourceUtil;
use App\Filament\Resources\Sep12FieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSep12Field extends CreateRecord
{
    protected static string $resource = Sep12FieldResource::class;

    /**
     * Mutates the form data before creating a resource.
     *
     * @param array<array-key, mixed> $data The form data.
     * @return array<array-key, mixed> $data The mutated form data.
    */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['choices'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'choices');

        return $data;
    }
}
