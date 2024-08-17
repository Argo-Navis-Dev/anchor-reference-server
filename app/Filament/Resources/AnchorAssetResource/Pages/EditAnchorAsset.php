<?php

namespace App\Filament\Resources\AnchorAssetResource\Pages;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Models\AnchorAsset;
use App\Stellar\Sep31CrossBorder\Sep31Helper;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep12Type;
use ArgoNavis\PhpAnchorSdk\shared\Sep31AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep38AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep38DeliveryMethod;
use Database\Seeders\AnchorAssetsSeeder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31InfoResponse;

use function Psy\debug;

class EditAnchorAsset extends EditRecord
{
    protected static string $resource = AnchorAssetResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /**
         * @var AnchorAsset $anchorAsset
         */
        $anchorAsset = $this->getRecord();
        AnchorAssetResourceHelper::populateSep31InfoBeforeFormLoad($data, $anchorAsset);
        AnchorAssetResourceHelper::populateSep38InfoBeforeFormLoad($data, $anchorAsset);

        $sep06WithdrawMethodsStr = $data['sep06_withdraw_methods'] ?? null;
        if($sep06WithdrawMethodsStr != null) {
            $sep06WithdrawMethods = explode(',', $sep06WithdrawMethodsStr);
            $data['sep06_withdraw_methods'] = $sep06WithdrawMethods;
        }

        $sep06DepositMethodsStr = $data['sep06_deposit_methods'] ?? null;
        if($sep06DepositMethodsStr != null) {
            $sep06DepositMethods = explode(',', $sep06DepositMethodsStr);
            $data['sep06_deposit_methods'] = $sep06DepositMethods;
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AnchorAssetResourceHelper::mutateFormDataBeforeSave($data);
    }



    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}