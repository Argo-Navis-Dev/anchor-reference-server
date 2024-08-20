<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\Sep06TransactionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * This class is responsible for creating SEP-06 transaction in the database.
 */
class CreateSep06Transaction extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = Sep06TransactionResource::class;
}
