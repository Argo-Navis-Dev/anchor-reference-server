<?php

namespace App\Filament\Resources\Sep12CustomerResource\Pages;

use App\Filament\Resources\Sep12CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep12Customers extends ListRecords
{
    protected static string $resource = Sep12CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
