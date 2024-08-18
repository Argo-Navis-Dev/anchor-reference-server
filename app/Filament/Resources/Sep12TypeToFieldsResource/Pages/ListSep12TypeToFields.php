<?php

namespace App\Filament\Resources\Sep12TypeToFieldsResource\Pages;

use App\Filament\Resources\Sep12TypeToFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep12TypeToFields extends ListRecords
{
    protected static string $resource = Sep12TypeToFieldsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
