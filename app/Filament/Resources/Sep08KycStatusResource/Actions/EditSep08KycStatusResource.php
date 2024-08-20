<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep08KycStatusResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Actions\EditAction;

/**
 *  Defines the edit SEP-08 KYC status action.
 */
class EditSep08KycStatusResource extends EditAction
{
    protected static string $resource = AnchorAssetResource::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->icon(FilamentIcon::resolve('actions::view-action') ?? 'heroicon-m-eye');
        $this->label(__('shared_lang.label.view'));
    }
}
