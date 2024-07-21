<?php

namespace App\Filament\Resources\Sep12CustomerResource\Pages;

use App\Filament\Resources\Sep12CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
class CreateSep12Customer extends CreateRecord
{
    protected static string $resource = Sep12CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        LOG::debug('Before save: ' . json_encode($data));
        return $data;
    }


}
