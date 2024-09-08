<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep06TransactionResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Actions\EditAction;

/**
 *  Defines the edit SEP-06 transaction action.
 */
class EditSep06TransactionResource extends EditAction
{
    protected static string $resource = AnchorAssetResource::class;

    /**
     * Sets up the edit actions.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->icon(FilamentIcon::resolve('actions::view-action') ?? 'heroicon-m-eye');
        $this->label(__('shared_lang.label.view'));
    }
}
