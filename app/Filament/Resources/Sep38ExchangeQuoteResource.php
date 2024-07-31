<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep38ExchangeQuoteResource\Pages;
use App\Filament\Resources\Sep38ExchangeQuoteResource\RelationManagers;
use App\Models\Sep38ExchangeQuote;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Sep38ExchangeQuoteResource extends Resource
{
    protected static ?string $model = Sep38ExchangeQuote::class;

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('id')
                    ->label(__('shared_lang.label.id'))
                    ->readOnly()
                    ->required(),
                TextInput::make('transaction_id')
                    ->label(__('sep38_lang.label.transaction_id')),
                TextInput::make('context')
                    ->label(__('sep38_lang.label.context'))
                    ->required(),
                DateTimePicker::make('expires_at')
                    ->label(__('sep38_lang.label.expires_at'))
                    ->required(),

                Section::make(__('sep38_lang.label.price_info'))
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
                    ->schema([
                        TextInput::make('account_id')
                        ->label(__('sep38_lang.label.account_id'))
                        ->required()
                        ->columnSpan(2),
                        TextInput::make('account_memo')
                            ->label(__('sep38_lang.label.account_memo'))])
                    ->columnSpan(3)
                    ->columns(3),

                Section::make(__('sep38_lang.label.sell'))
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

                Textarea::make('fee')
                    ->label(__('sep38_lang.label.fee'))
                    ->required()
                    ->columnSpanFull(),
                ResourceUtil::getModelTimestampFormControls(1)
            ])
            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            Split::make([
                TextColumn::make('sell_asset')
                    ->limit(20)
                    ->description(__('sep38_lang.label.sell_asset'))
                    ->searchable(),
                TextColumn::make('sell_amount')
                    ->description(__('sep38_lang.label.sell_amount'))
                    ->searchable(),
                TextColumn::make('buy_asset')
                    ->limit(20)
                    ->description(__('sep38_lang.label.buy_asset'))
                    ->searchable(),
                TextColumn::make('buy_amount')
                    ->description(__('sep38_lang.label.buy_amount'))
                    ->searchable(),
                TextColumn::make('context')
                    ->description(__('sep38_lang.label.context'))
                    ->searchable(),
            ])
        ];
        return $table
            ->columns($columns)
            ->filters([
                //
            ])
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
}
