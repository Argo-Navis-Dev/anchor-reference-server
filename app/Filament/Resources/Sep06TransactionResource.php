<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep06TransactionResource\Pages;
use App\Filament\Resources\Sep06TransactionResource\RelationManagers;
use App\Models\AnchorAsset;
use App\Models\Sep06Transaction;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Filament\Support\get_model_label;

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
            ->columns(Sep06And24ResourceUtil::getTableColumns(true))
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
}
