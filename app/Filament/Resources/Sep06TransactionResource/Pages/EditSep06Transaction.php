<?php

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\Sep06TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep06Transaction extends EditRecord
{
    protected static string $resource = Sep06TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
