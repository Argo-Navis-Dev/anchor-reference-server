<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep08KycStatusResource\Pages;

use App\Filament\Resources\Sep08KycStatusResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * This class is responsible for creating SEP-08 KYC status record in the database.
 */
class CreateSep08KycStatus extends CreateRecord
{
    protected static string $resource = Sep08KycStatusResource::class;
}
