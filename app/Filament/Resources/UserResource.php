<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('user_lang.label.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('user_lang.label.email'))
                    ->email()
                    ->required(),
                Toggle::make("reset_password")
                    ->live()
                    ->columnSpan(2)
                    ->label(__("user_lang.label.reset_password")),
                TextInput::make('password')
                    ->label(__('user_lang.label.password'))
                    ->password()
                    ->required()
                    ->hidden(fn (Get $get): bool => ! $get("reset_password"))
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label(__('user_lang.label.password_confirmation'))
                    ->hidden(fn (Get $get): bool => ! $get("reset_password"))
                    ->password()
                    ->required(),
                ResourceUtil::getModelTimestampFormControls(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('user_lang.label.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('user_lang.label.email'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('shared_lang.label.updated_at'))
                    ->dateTime()
                    ->sortable()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('user_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('user_lang.entity.names');
    }
}
