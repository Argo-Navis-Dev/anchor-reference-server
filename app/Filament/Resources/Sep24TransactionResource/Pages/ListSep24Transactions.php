<?php

namespace App\Filament\Resources\Sep24TransactionResource\Pages;

use App\Filament\Resources\Sep24TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSep24Transactions extends ListRecords
{
    protected static string $resource = Sep24TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
