<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep31TransactionResource\Pages;

use App\Filament\Resources\Sep31TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSep31Transaction extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = Sep31TransactionResource::class;
}
