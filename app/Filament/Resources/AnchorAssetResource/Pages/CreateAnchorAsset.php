<?php

namespace App\Filament\Resources\AnchorAssetResource\Pages;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateAnchorAsset extends CreateRecord
{
    protected static string $resource = AnchorAssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        LOG::debug("Create mutateFormDataBeforeCreate");
        return AnchorAssetResourceHelper::mutateFormDataBeforeSave($data);
    }
}
