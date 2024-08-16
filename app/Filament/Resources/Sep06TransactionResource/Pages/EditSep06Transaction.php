<?php

namespace App\Filament\Resources\Sep06TransactionResource\Pages;

use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Filament\Resources\Sep06And24ResourceUtil;
use App\Filament\Resources\Sep06TransactionResource;
use App\Models\AnchorAsset;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditSep06Transaction extends EditRecord
{
    protected static string $resource = Sep06TransactionResource::class;

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
