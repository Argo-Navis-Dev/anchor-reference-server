<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep06TransactionResource\Actions\EditSep06TransactionResource;
use App\Filament\Resources\Sep06TransactionResource\Pages;
use App\Filament\Resources\Sep06TransactionResource\RelationManagers;
use App\Models\Sep06Transaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class Sep06TransactionResource extends Resource
{
    protected static ?string $model = Sep06Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Sep06And24ResourceUtil::getFormControls(true));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Sep06And24ResourceUtil::getTableColumns($table, true))
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                EditSep06TransactionResource::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListSep06Transactions::route('/'),
            'create' => Pages\CreateSep06Transaction::route('/create'),
            'edit' => Pages\EditSep06Transaction::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('sep06_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep06_lang.entity.names');
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
