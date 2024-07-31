<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep08KycStatusResource\Pages;
use App\Filament\Resources\Sep08KycStatusResource\RelationManagers;
use App\Models\Sep08KycStatus;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Sep08KycStatusResource extends Resource
{
    protected static ?string $model = Sep08KycStatus::class;

    protected static ?string $navigationIcon = 'heroicon-m-check-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('stellar_address')
                    ->columnSpan(3)
                    ->label(__('sep08_lang.label.stellar_address'))
                    ->required(),
                Toggle::make('approved')
                    ->label(__('sep08_lang.label.approved'))
                    ->required(),
                Toggle::make('rejected')
                    ->label(__('sep08_lang.label.rejected'))
                    ->required(),
                Toggle::make('pending')
                    ->label(__('sep08_lang.label.pending'))
                    ->required(),
                ResourceUtil::getModelTimestampFormControls(1)
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stellar_address')
                    ->label(__('sep08_lang.label.stellar_address'))
                    ->searchable(),
                IconColumn::make('approved')
                    ->label(__('sep08_lang.label.approved'))
                    ->boolean(),
                IconColumn::make('rejected')
                    ->label(__('sep08_lang.label.rejected'))
                    ->boolean(),
                IconColumn::make('pending')
                    ->label(__('sep08_lang.label.pending'))
                    ->boolean(),
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
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSep08KycStatuses::route('/'),
            'create' => Pages\CreateSep08KycStatus::route('/create'),
            'edit' => Pages\EditSep08KycStatus::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('sep08_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep08_lang.entity.names');
    }
}
