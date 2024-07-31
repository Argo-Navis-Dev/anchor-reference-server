<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep38RateResource\Pages;
use App\Filament\Resources\Sep38RateResource\RelationManagers;
use App\Models\Sep38Rate;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Sep38RateResource extends Resource
{
    protected static ?string $model = Sep38Rate::class;

    protected static ?string $navigationIcon = 'heroicon-s-arrow-path-rounded-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('sell_asset')
                    ->label(__('sep38_lang.label.sell_asset'))
                    ->required(),
                TextInput::make('buy_asset')
                    ->label(__('sep38_lang.label.buy_asset'))
                    ->required(),
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
                    ->label(__('sep38_lang.label.sell_asset'))
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('buy_asset')
                    ->label(__('sep38_lang.label.buy_asset'))
                    ->limit(20)
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
                    ->label(__('sep38_lang.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('sep38_lang.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
}
