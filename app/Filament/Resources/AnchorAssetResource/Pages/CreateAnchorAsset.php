<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\AnchorAssetResource\Pages;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * This class is responsible for creating an anchor asset record in the database.
 */
class CreateAnchorAsset extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = AnchorAssetResource::class;

    /**
     * Mutates the form data before creating a resource.
     *
     * @param array<array-key, mixed> $data The form data.
     * @return array<array-key, mixed> $data The mutated form data.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AnchorAssetResourceHelper::mutateFormDataBeforeSave($data);
    }
}
