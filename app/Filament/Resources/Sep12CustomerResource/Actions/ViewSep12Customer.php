<?php

namespace App\Filament\Resources\Sep12CustomerResource\Actions;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Util\AnchorAssetResourceHelper;
use App\Filament\Resources\Sep12CustomerResource;
use App\Filament\Resources\Sep12CustomerResource\Util\Sep12CustomerResourceHelper;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ViewSep12Customer extends ViewAction
{
    protected static string $resource = Sep12CustomerResource::class;


    protected function setUp(): void
    {
        parent::setUp();
        $this->mutateRecordDataUsing(function (Model $record, array $data): array {
            Sep12CustomerResourceHelper::populateCustomerFieldsBeforeFormLoad($data, $record);
            return $data;
        });
    }
}
