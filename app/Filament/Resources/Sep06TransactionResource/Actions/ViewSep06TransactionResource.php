<?php

namespace App\Filament\Resources\Sep06TransactionResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Filament\Resources\Sep06And24ResourceUtil;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;

class ViewSep06TransactionResource extends ViewAction
{
    protected static string $resource = AnchorAssetResource::class;


    protected function setUp(): void
    {
        parent::setUp();
        $this->mutateRecordDataUsing(function (Model $record, array $data): array {
            Sep06And24ResourceUtil::populateDataBeforeFormLoad($data, $record);
            return $data;
        });
    }
}
