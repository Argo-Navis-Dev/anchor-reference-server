<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12TypeToFieldsResource\Pages;

use App\Filament\Resources\Sep12TypeToFieldsResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * This class is responsible for creating SEP-12 customer type to field record in the database.
 */
class CreateSep12TypeToFields extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = Sep12TypeToFieldsResource::class;
}
