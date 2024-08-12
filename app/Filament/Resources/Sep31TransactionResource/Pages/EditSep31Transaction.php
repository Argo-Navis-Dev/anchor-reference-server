<?php

namespace App\Filament\Resources\Sep31TransactionResource\Pages;

use App\Filament\Resources\Sep06And24ResourceUtil;
use App\Filament\Resources\Sep31TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep31Transaction extends EditRecord
{
    protected static string $resource = Sep31TransactionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $feeDetails = $data['fee_details'];
        if($feeDetails != null) {
            $data['fee_details'] = json_decode($feeDetails, true);
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
