<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep24TransactionResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Actions\EditAction;

/**
 * Defines the edit SEP-24 transaction action.
 */
class EditSep24TransactionResource extends EditAction
{
    protected static string $resource = AnchorAssetResource::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->icon(FilamentIcon::resolve('actions::view-action') ?? 'heroicon-m-eye');
        $this->label(__('shared_lang.label.view'));
    }
}
