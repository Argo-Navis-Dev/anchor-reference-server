<?php

namespace App\Filament\Resources\Sep24TransactionResource\Pages;

use App\Filament\Resources\Sep06And24ResourceUtil;
use App\Filament\Resources\Sep24TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep24Transaction extends EditRecord
{
    protected static string $resource = Sep24TransactionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $model = $this->getRecord();
        Sep06And24ResourceUtil::populateDataBeforeFormLoad($data, $model);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
