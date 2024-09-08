<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12CustomerResource\Pages;

use App\Filament\Resources\Sep12CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep12Customers extends ListRecords
{
    /**
     * @var string $resource The db entity to be listed.
     */
    protected static string $resource = Sep12CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
