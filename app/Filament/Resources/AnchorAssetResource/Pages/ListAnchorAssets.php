<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\AnchorAssetResource\Pages;

use App\Filament\Resources\AnchorAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Lists the Anchor assets from the database.
 */
class ListAnchorAssets extends ListRecords
{
    /**
     * @var string The DB entity to be listed.
     */
    protected static string $resource = AnchorAssetResource::class;

    /**
     * Returns the possible header actions in the list.
     *
     * @return array<mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
