<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep24TransactionResource\Pages;

use App\Filament\Resources\Sep24TransactionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * This class is responsible for creating a SEP-24 transaction record in the database.
 */
class CreateSep24Transaction extends CreateRecord
{
    protected static string $resource = Sep24TransactionResource::class;
}
