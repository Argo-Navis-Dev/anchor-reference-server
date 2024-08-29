<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 *   The UI. controls definitions for a user record from the database.
 */
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
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->label(__('user_lang.label.email'))
                    ->unique(ignorable: $form->getRecord())
                    ->maxLength(320)
                    ->email()
                    ->required(),
                Toggle::make("reset_password")
                    ->label(__("user_lang.label.reset_password"))
                    ->live()
                    ->hidden(function(?Model $record) use ($form){
                        if($record == null) {
                            return true;
                        }
                        return $form->getOperation() == 'view';
                    })
                    ->columnSpan(2),
                TextInput::make('password')
                    ->label(__('user_lang.label.password'))
                    ->password()
                    ->minLength(8)
                    ->maxLength(255)
                    ->required()
                    ->hidden(function(Get $get) use ($form): bool {
                        $record = $form->getRecord();
                        if($record == null) {
                            return false;
                        }
                        return !$get("reset_password");
                    })
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label(__('user_lang.label.password_confirmation'))
                    ->minLength(8)
                    ->maxLength(255)
                    ->hidden(function(Get $get) use ($form): bool {
                        $record = $form->getRecord();
                        if($record == null) {
                            return false;
                        }
                        return !$get("reset_password");
                    })
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
                    ->sortable()
                    ->limit(35)
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('user_lang.label.email'))
                    ->sortable()
                    ->limit(35)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->toggleable()
                    ->sortable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('shared_lang.label.updated_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable()
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
