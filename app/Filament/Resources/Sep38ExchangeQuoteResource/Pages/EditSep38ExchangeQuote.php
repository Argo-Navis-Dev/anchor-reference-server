<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep38ExchangeQuoteResource\Pages;

use App\Filament\Resources\Sep38ExchangeQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 *  This class is responsible for editing SEP-38 exchange quote record in the database.
 */
class EditSep38ExchangeQuote extends EditRecord
{
    protected static string $resource = Sep38ExchangeQuoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $feeDetails = $data['fee'] ?? null;
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

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
        ];
    }
}
