<?php

namespace App\Filament\Resources;

use App\Models\Sep12Customer;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;

class ResourceUtil
{

    public static function getModelTimestampFormControls(int $colspan): Section {
        return Section::make()
            ->columnSpan($colspan)
            ->schema([
                Placeholder::make('created_at')
                    ->label(__('shared_lang.label.created_at'))
                    ->columns(1)
                    ->content(fn(Model $record): ?string => $record->created_at?->diffForHumans()),
                Placeholder::make('updated_at')
                    ->label(__('shared_lang.label.updated_at'))
                    ->columns(1)
                    ->content(fn(Model $record): ?string => $record->updated_at?->diffForHumans())
            ]);
    }
}