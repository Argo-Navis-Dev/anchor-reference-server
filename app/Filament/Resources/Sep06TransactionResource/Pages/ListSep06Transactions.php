<?php

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\Sep06TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep06Transactions extends ListRecords
{
    protected static string $resource = Sep06TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
