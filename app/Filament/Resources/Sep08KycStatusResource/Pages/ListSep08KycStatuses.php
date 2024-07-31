<?php

namespace App\Filament\Resources\Sep08KycStatusResource\Pages;

use App\Filament\Resources\Sep08KycStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep08KycStatuses extends ListRecords
{
    protected static string $resource = Sep08KycStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
