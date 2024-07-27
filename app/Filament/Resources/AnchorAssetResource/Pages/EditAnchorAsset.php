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

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            $sep31InfoJson = $this->processSep31InfoBeforeSave($data);
            $sep38InfoJson = $this->processSep38InfoBeforeSave($data);
            $data['sep31_info'] = $sep31InfoJson;
            $data['sep38_info'] = $sep38InfoJson;
        } catch (InvalidAsset $e) {
            LOG::debug($e->getMessage());
        }
        return $data;
    }

    /**
     * @throws InvalidAsset
     */
    private function processSep31InfoBeforeSave(array $data): string
    {
        $asset = new IdentificationFormatAsset(
            schema: $data['schema'],
            code: $data['code'],
            issuer: $data['issuer'],
        );

        $sep31CfgSep12SenderTypes = $data['sep31_cfg_sep12_sender_types'] ?? null;
        $senderTypes = [];
        if ($sep31CfgSep12SenderTypes != null) {
            foreach ($sep31CfgSep12SenderTypes as $type) {
                $senderTypes[] = new Sep12Type(
                    name: $type['name'],
                    description: $type['description'],
                );
            }
        }


        $sep31CfgSep12ReceiverTypes = $data['sep31_cfg_sep12_receiver_types'] ?? null;
        $receiverTypes = [];
        if($sep31CfgSep12ReceiverTypes != null) {
            foreach ($sep31CfgSep12ReceiverTypes as $type) {
                $receiverTypes[] = new Sep12Type(
                    name: $type['name'],
                    description: $type['description'],
                );
            }
        }
        $sep31Asset = new Sep31AssetInfo(
            asset: $asset,
            sep12SenderTypes: $senderTypes,
            sep12ReceiverTypes: $receiverTypes,
            quotesSupported: $data['sep31_cfg_quotes_supported'] ?? null,
            quotesRequired: $data['sep31_cfg_quotes_required'] ?? null,
        );

        return json_encode($sep31Asset->toJson());
    }

    /**
     * @throws InvalidAsset
     */
    private function processSep38InfoBeforeSave(array $data): string
    {
        $asset = new IdentificationFormatAsset(
            schema: $data['schema'],
            code: $data['code'],
            issuer: $data['issuer'],
        );

        $sellDeliveryMethods = $data['sep38_cfg_sell_delivery_methods'] ?? null;
        $sellMethodsByType = [];
        if($sellDeliveryMethods != null) {
            foreach ($sellDeliveryMethods as $method) {
                $sellMethodsByType[] = new Sep38DeliveryMethod(
                    name: $method['name'],
                    description: $method['description'],
                );
            }
        }

        $buyDeliveryMethods = $data['sep38_cfg_buy_delivery_methods'] ?? null;
        $buyMethodsByType = [];
        if($buyDeliveryMethods != null) {
            foreach ($buyDeliveryMethods as $method) {
                $buyMethodsByType[] = new Sep38DeliveryMethod(
                    name: $method['name'],
                    description: $method['description'],
                );
            }
        }

        $sep38AssetInfo = new Sep38AssetInfo(
            asset: $asset,
            sellDeliveryMethods: $sellMethodsByType,
            buyDeliveryMethods: $buyMethodsByType,
            countryCodes: $data['sep38_cfg_country_codes'] ?? null,
        );

        $sep38AssetInfoJson = $sep38AssetInfo->toJson();

        $result = array(
            'sell_delivery_methods' => $sep38AssetInfoJson['sell_delivery_methods'] ?? [],
            'buy_delivery_methods' => $sep38AssetInfoJson['buy_delivery_methods'] ?? [],
            'country_codes' => $sep38AssetInfoJson['country_codes'] ?? []
        );
        return json_encode($result);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}