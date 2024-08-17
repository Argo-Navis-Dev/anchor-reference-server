<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep06TransactionResource\Actions\EditSep31TransactionResource;
use App\Filament\Resources\Sep24TransactionResource\Actions\EditSep24TransactionResource;
use App\Filament\Resources\Sep24TransactionResource\Pages;
use App\Models\Sep24Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Sep24TransactionResource extends Resource
{
    protected static ?string $model = Sep24Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Sep06And24ResourceUtil::getFormControls(false));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Sep06And24ResourceUtil::getTableColumns($table, false))
            ->filters([
                //
            ])
            ->actions([
                EditSep24TransactionResource::make()
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
            'index' => Pages\ListSep24Transactions::route('/'),
            'create' => Pages\CreateSep24Transaction::route('/create'),
            'edit' => Pages\EditSep24Transaction::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('sep24_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep24_lang.entity.names');
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
