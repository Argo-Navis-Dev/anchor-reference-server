<?php

namespace App\Filament\Resources\Sep06TransactionResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\Sep06And24ResourceUtil;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;

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
