<?php

namespace App\Filament\Resources\AnchorAssetResource\Pages;

use App\Filament\Resources\AnchorAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnchorAssets extends ListRecords
{
    protected static string $resource = AnchorAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
