<?php

namespace App\Filament\Resources\AnchorAssetResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Stellar\Sep31CrossBorder\Sep31Helper;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ViewAnchorAsset extends ViewAction
{
    protected static string $resource = AnchorAssetResource::class;


    protected function setUp(): void
    {
        parent::setUp();
        $this->mutateRecordDataUsing(function (Model $record, array $data): array {
            AnchorAssetResourceHelper::populateSep31InfoBeforeFormLoad($data, $record);
            return $data;
        });
    }

    /*public function getModel(): string
    {
        $m = $this->getCustomModel() ?? $this->getTable()->getModel();
        LOG::debug(json_encode($m));
        return $m;
    }*/

}
