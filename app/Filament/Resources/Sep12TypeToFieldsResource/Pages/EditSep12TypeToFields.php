<?php

namespace App\Filament\Resources\Sep12TypeToFieldsResource\Pages;

use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Filament\Resources\ResourceUtil;
use App\Filament\Resources\Sep12TypeToFieldsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep12TypeToFields extends EditRecord
{
    protected static string $resource = Sep12TypeToFieldsResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['required_fields'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'required_fields');
        $data['optional_fields'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'optional_fields');
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $requiredFieldsStr = $data['required_fields'] ?? null;
        if($requiredFieldsStr != null) {
            $requiredFields = explode(',', $requiredFieldsStr);
            $data['required_fields'] = $requiredFields;
        }
        $optionalFieldsStr = $data['optional_fields'] ?? null;
        if($optionalFieldsStr != null) {
            $optionalFields = explode(',', $optionalFieldsStr);
            $data['optional_fields'] = $optionalFields;
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
