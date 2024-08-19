<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\Sep12TypeToFieldsResource\Pages;
use App\Models\Sep12Field;
use App\Models\Sep12TypeToFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 *  The UI. controls definitions for a SEP-12 customer type to field record from the database.
 */
class Sep12TypeToFieldsResource extends Resource
{
    protected static ?string $model = Sep12TypeToFields::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('type')
                    ->columnSpanFull()
                    ->required(),
                Select::make('required_fields')
                    ->label(__('sep12_lang.label.fields.required'))
                    ->multiple(true)
                    ->options(function(?Model $record)  {
                        return self::getFieldsSelectOptions();
                    })
                    ->columnSpanFull(),
                Select::make('optional_fields')
                    ->label(__('sep12_lang.label.fields.optional'))
                    ->multiple(true)
                    ->options(function(?Model $record)  {
                        return self::getFieldsSelectOptions();
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('sep12_lang.label.fields.type'))
                    ->searchable(),
                TextColumn::make('required_fields')
                    ->listWithLineBreaks()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        return self::formatTableFieldsAsHtml($state);
                    })
                    ->label(__('sep12_lang.label.fields.required'))
                    ->searchable(),
                TextColumn::make('optional_fields')
                    ->label(__('sep12_lang.label.fields.optional'))
                    ->html()
                    ->formatStateUsing(function ($state) {
                        return self::formatTableFieldsAsHtml($state);
                    })
                    ->searchable(),
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
            'index' => Pages\ListSep12TypeToFields::route('/'),
            'create' => Pages\CreateSep12TypeToFields::route('/create'),
            'edit' => Pages\EditSep12TypeToFields::route('/{record}/edit'),
        ];
    }

    private static function getFieldsSelectOptions(): array
    {
        $fields = Sep12Field::all();
        $result = array();
        foreach ($fields as $field) {
            $keyConverted = str_replace(".", "_", $field->key);
            $langKey = 'sep12_lang.label.' . $keyConverted;
            $label = __($langKey);
            $label = $label != $langKey ? $label : $langKey;
            $result[$field->key] = $label;
        }
        return $result;
    }

    public static function getModelLabel(): string
    {
        return __('sep12_lang.entity.sep12_type_to_fields.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep12_lang.entity.sep12_type_to_fields.names');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Formats the required and optional fields to be shown in table.
     * @param $state
     * @return string
     */
    private static function formatTableFieldsAsHtml($state): string
    {
        $values = array_map('trim', explode(',', $state));
        $html = '';
        foreach ($values as $value) {
            $html = $html . __("sep12_lang.label.${value}") . '<br>';
        }

        return $html;
    }
}
