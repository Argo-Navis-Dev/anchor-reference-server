<?php

namespace App\Filament\Resources\Sep38RateResource\Pages;

use App\Filament\Resources\Sep38RateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep38Rates extends ListRecords
{
    protected static string $resource = Sep38RateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
