<?php

namespace App\Filament\Resources\Sep38RateResource\Pages;

use App\Filament\Resources\Sep38RateResource;
use App\Models\AnchorAsset;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditSep38Rate extends EditRecord
{
    protected static string $resource = Sep38RateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
