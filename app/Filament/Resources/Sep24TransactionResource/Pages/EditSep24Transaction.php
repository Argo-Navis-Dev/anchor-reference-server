<?php

namespace App\Filament\Resources\Sep24TransactionResource\Pages;

use App\Filament\Resources\Sep24TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep24Transaction extends EditRecord
{
    protected static string $resource = Sep24TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
