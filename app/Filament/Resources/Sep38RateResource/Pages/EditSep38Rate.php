<?php

namespace App\Filament\Resources\Sep38RateResource\Pages;

use App\Filament\Resources\Sep38RateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
