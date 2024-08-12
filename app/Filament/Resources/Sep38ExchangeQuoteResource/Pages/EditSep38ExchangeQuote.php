<?php

namespace App\Filament\Resources\Sep38ExchangeQuoteResource\Pages;

use App\Filament\Resources\Sep38ExchangeQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep38ExchangeQuote extends EditRecord
{
    protected static string $resource = Sep38ExchangeQuoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /*$feeDetails = $data['fee_details'] _ ??;
        if($feeDetails != null) {
            $data['fee_details'] = json_decode($feeDetails, true);
        }*/
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
