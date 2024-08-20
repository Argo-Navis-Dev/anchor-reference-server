<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\Sep08KycStatusResource\Actions\EditSep08KycStatusResource;
use App\Filament\Resources\Sep08KycStatusResource\Pages;
use App\Models\Sep08KycStatus;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 *  The UI. controls definitions for a SEP-08 KYC status record from the database.
 */
class Sep08KycStatusResource extends Resource
{
    protected static ?string $model = Sep08KycStatus::class;

    protected static ?string $navigationIcon = 'heroicon-m-check-circle';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('stellar_address')
                    ->columnSpan(3)
                    ->disabled()
                    ->label(__('sep08_lang.label.stellar_address'))
                    ->required(),
                Toggle::make('approved')
                    ->disabled()
                    ->label(__('sep08_lang.label.approved'))
                    ->required(),
                Toggle::make('rejected')
                    ->disabled()
                    ->label(__('sep08_lang.label.rejected'))
                    ->required(),
                Toggle::make('pending')
                    ->disabled()
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
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->searchable(),
                IconColumn::make('approved')
                    ->label(__('sep08_lang.label.approved'))
                    ->searchable()
                    ->sortable()
                    ->boolean(),
                IconColumn::make('rejected')
                    ->label(__('sep08_lang.label.rejected'))
                    ->searchable()
                    ->sortable()
                    ->boolean(),
                IconColumn::make('pending')
                    ->label(__('sep08_lang.label.pending'))
                    ->searchable()
                    ->sortable()
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
            ->recordAction(null)
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->actions([
                EditSep08KycStatusResource::make()
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
