<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep38RateResource\Pages;

use App\Filament\Resources\Sep38RateResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * This class is responsible for creating SEP-38 rate record in the database.
 */
class CreateSep38Rate extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = Sep38RateResource::class;
}
