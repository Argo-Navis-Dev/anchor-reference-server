<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\Sep38RateResource\Pages;
use App\Models\Sep38Rate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 *  The UI. controls definitions for a SEP-38 rate resource record from the database.
 */
class Sep38RateResource extends Resource
{
    protected static ?string $model = Sep38Rate::class;
    protected static ?string $navigationIcon = 'heroicon-s-arrow-path-rounded-square';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sell_asset')
                    ->required()
                    ->label(__('sep38_lang.label.sell_asset'))
                    ->options(ResourceUtil::getAnchorAssetsDataSourceForSelect()),
                Select::make('buy_asset')
                    ->required()
                    ->label(__('sep38_lang.label.buy_asset'))
                    ->options(ResourceUtil::getAnchorAssetsDataSourceForSelect()),
                TextInput::make('rate')
                    ->label(__('sep38_lang.label.rate'))
                    ->required()
                    ->numeric(),
                TextInput::make('fee_percent')
                    ->label(__('sep38_lang.label.fee_percent'))
                    ->required()
                    ->numeric()
                    ->default(1),
                ResourceUtil::getModelTimestampFormControls(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sell_asset')
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->iconPosition(IconPosition::After)
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->label(__('sep38_lang.label.sell_asset'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('buy_asset')
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->iconPosition(IconPosition::After)
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->label(__('sep38_lang.label.buy_asset'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('rate')
                    ->label(__('sep38_lang.label.rate'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fee_percent')
                    ->label(__('sep38_lang.label.fee_percent'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('shared_lang.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSep38Rates::route('/'),
            'create' => Pages\CreateSep38Rate::route('/create'),
            'edit' => Pages\EditSep38Rate::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('sep38_lang.entity.rate.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep38_lang.entity.rate.names');
    }

    public static function getNavigationLabel(): string
    {
        return __('sep38_lang.entity.rate.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('sep38_lang.navigation.group');
    }
}
