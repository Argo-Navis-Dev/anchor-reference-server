<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\Sep38ExchangeQuoteResource\Actions\EditSep38EExchangeQuoteResource;
use App\Filament\Resources\Sep38ExchangeQuoteResource\Pages;
use App\Filament\Resources\Sep38ExchangeQuoteResource\RelationManagers;
use App\Models\Sep38ExchangeQuote;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 *  The UI. controls definitions for a SEP-38 exchange quote transaction record from the database.
 */
class Sep38ExchangeQuoteResource extends Resource
{
    protected static ?string $model = Sep38ExchangeQuote::class;

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('transaction_id')
                    ->disabled()
                    ->label(__('sep38_lang.label.transaction_id')),
                TextInput::make('context')
                    ->disabled()
                    ->label(__('sep38_lang.label.context'))
                    ->required(),
                DateTimePicker::make('expires_at')
                    ->disabled()
                    ->label(__('sep38_lang.label.expires_at'))
                    ->required(),

                Section::make(__('sep38_lang.label.price_info'))
                    ->disabled()
                    ->schema([
                        TextInput::make('price')
                            ->label(__('sep38_lang.label.price'))
                            ->required(),
                        TextInput::make('total_price')
                            ->label(__('sep38_lang.label.total_price'))
                            ->required(),
                    ])
                    ->columnSpan(1),
                Fieldset::make(__('sep38_lang.label.account_info'))
                    ->disabled()
                    ->schema([
                        TextInput::make('account_id')
                        ->label(__('sep38_lang.label.account_id'))
                        ->required()
                        ->columnSpan(2),
                        TextInput::make('account_memo')
                            ->label(__('sep38_lang.label.account_memo'))])
                    ->columnSpan(2)
                    ->columns(3),

                Section::make(__('sep38_lang.label.sell'))
                    ->disabled()
                    ->columns(3)
                    ->schema([
                        TextInput::make('sell_asset')
                            ->label(__('sep38_lang.label.sell_asset'))
                            ->required(),
                        TextInput::make('sell_amount')
                            ->label(__('sep38_lang.label.sell_amount'))
                            ->required(),
                        TextInput::make('sell_delivery_method')
                            ->label(__('sep38_lang.label.sell_delivery_method')),
                    ]),

                Section::make(__('sep38_lang.label.buy'))
                    ->disabled()
                    ->columns(3)
                    ->schema([
                        TextInput::make('buy_asset')
                            ->label(__('sep38_lang.label.buy_asset'))
                            ->required(),
                        TextInput::make('buy_amount')
                            ->label(__('sep38_lang.label.buy_amount'))
                            ->required(),
                        TextInput::make('buy_delivery_method')
                            ->label(__('sep38_lang.label.buy_delivery_method')),
                    ]),
                ResourceUtil::getFeeDetailsFormControl(),
                ResourceUtil::getModelTimestampFormControls(1)
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        $tmp = [];
        $columns = [
            Split::make([
                TextColumn::make('sell_asset')
                    ->description(__('sep38_lang.label.sell_asset'))
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->searchable(),
                TextColumn::make('sell_amount')
                    ->description(__('sep38_lang.label.sell_amount'))
                    ->searchable()
                    ->searchable(),
                TextColumn::make('buy_asset')
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->searchable()
                    ->sortable()
                    ->iconPosition(IconPosition::After)
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->description(__('sep38_lang.label.buy_asset'))
                    ->searchable(),
                TextColumn::make('buy_amount')
                    ->description(__('sep38_lang.label.buy_amount'))
                    ->searchable()
                    ->searchable(),
                TextColumn::make('context')
                    ->description(__('sep38_lang.label.context'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->description(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->hidden(function()  use ($table) {
                        $createdAt = $table->getColumn('created_at');
                        return $createdAt->isToggledHidden();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->description(__('shared_lang.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->hidden(function()  use ($table) {
                        $updatedAt = $table->getColumn('updated_at');
                        return $updatedAt->isToggledHidden();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
        ];
        return $table
            ->columns($columns)
            ->recordUrl(null)
            ->recordAction(null)
            ->filters([
                //
            ])
            ->actions([
                EditSep38EExchangeQuoteResource::make()
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
            'index' => Pages\ListSep38ExchangeQuotes::route('/'),
            'create' => Pages\CreateSep38ExchangeQuote::route('/create'),
            'edit' => Pages\EditSep38ExchangeQuote::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('sep38_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep38_lang.entity.names');
    }

    public static function getNavigationLabel(): string
    {
        return __('sep38_lang.entity.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('sep38_lang.navigation.group');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
