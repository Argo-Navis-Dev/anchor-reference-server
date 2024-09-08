<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 *  This class is responsible for creating user record in the database.
 */
class CreateUser extends CreateRecord
{
    /**
     * @var string $resource The db entity to be created.
     */
    protected static string $resource = UserResource::class;
}
