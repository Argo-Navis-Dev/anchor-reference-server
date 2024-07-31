<?php

namespace App\Filament\Resources\Sep31TransactionResource\Pages;

use App\Filament\Resources\Sep31TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep31Transactions extends ListRecords
{
    protected static string $resource = Sep31TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
