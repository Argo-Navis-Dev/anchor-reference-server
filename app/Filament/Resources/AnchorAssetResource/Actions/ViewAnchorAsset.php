<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\AnchorAssetResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Helper\AnchorAssetResourceHelper;
use App\Models\AnchorAsset;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Log;

use function json_encode;

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
         * @param AnchorAsset $record The entity to be shown.
         * @param array<array-key, mixed> $data The view dialog model.
         */
        $this->mutateRecordDataUsing(function (AnchorAsset $record, array $data): array {
            Log::debug(
                'Preparing data for view action.',
                ['context' => 'anchor_asset_ui', 'data' => json_encode($data)],
            );

            AnchorAssetResourceHelper::populateSep31InfoBeforeFormLoad($data, $record);
            AnchorAssetResourceHelper::populateSep38InfoBeforeFormLoad($data, $record);
            Log::debug(
                'Data prepared for view action.',
                ['context' => 'anchor_asset_ui', 'data' => json_encode($data)],
            );

            return $data;
        });
    }
}
