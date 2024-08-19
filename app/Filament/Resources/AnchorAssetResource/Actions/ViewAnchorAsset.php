<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\AnchorAssetResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Models\AnchorAsset;
use Filament\Tables\Actions\ViewAction;

/**
 * Defines the view Anchor asset action.
 */
class ViewAnchorAsset extends ViewAction
{
    protected static string $resource = AnchorAssetResource::class;

    /**
     * Sets up the view.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        /**
         * The application instance.
         *
         * @param AnchorAsset $record The entity to be shown.
         * @param array<array-key, mixed> $data The view dialog model.
         */
        $this->mutateRecordDataUsing(function (AnchorAsset $record, array $data): array {
            AnchorAssetResourceHelper::populateSep31InfoBeforeFormLoad($data, $record);
            AnchorAssetResourceHelper::populateSep38InfoBeforeFormLoad($data, $record);
            return $data;
        });
    }
}
