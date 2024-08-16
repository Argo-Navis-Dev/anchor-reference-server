<?php

namespace App\Filament\Resources\Sep08KycStatusResource\Pages;

use App\Filament\Resources\Sep08KycStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep08KycStatus extends EditRecord
{
    protected static string $resource = Sep08KycStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
        ];
    }
}
