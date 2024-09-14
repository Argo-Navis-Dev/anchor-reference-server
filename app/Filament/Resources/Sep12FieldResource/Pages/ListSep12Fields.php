<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12FieldResource\Pages;

use App\Filament\Resources\Sep12FieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep12Fields extends ListRecords
{
    protected static string $resource = Sep12FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
