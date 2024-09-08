<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\Sep12FieldResource\Pages;
use App\Filament\Resources\Sep12FieldResource\RelationManagers;
use App\Models\Sep12Field;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 *  The UI. controls definitions for a SEP-12 field record from the database.
 */
class Sep12FieldResource extends Resource
{
    protected static ?string $model = Sep12Field::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        $schema = [
            TextInput::make('key')
                ->required()
                ->label(__('sep12_lang.label.key')),
            TextArea::make('desc')
                ->rows(4)
                ->label(__('shared_lang.label.description')),
            Grid::make()
                ->columns(2)
                ->schema([
                    self::createTypeField($form),
                    self::createChoicesField($form)
                ]),
            Toggle::make("requires_verification")
                ->label(__('sep12_lang.label.field.requires_verification')),
            TextInput::make('lang')
                ->label(__('sep12_lang.label.field.lang')),
            ResourceUtil::getModelTimestampFormControls(1)
        ];
        return $form
            ->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            TextColumn::make('key')
                ->label(__('sep12_lang.label.key'))
                ->searchable()
                ->sortable(),
            TextColumn::make('desc')
                ->label(__('shared_lang.label.description'))
                ->searchable()
                ->limit(50)
                ->sortable(),
            ToggleColumn::make('requires_verification')
                ->label(__('sep12_lang.label.field.requires_verification')),
            TextColumn::make('lang')
                ->label(__('sep12_lang.label.field.lang')),
            TextColumn::make('created_at')
                ->label(__('shared_lang.label.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label(__('shared_lang.label.updated_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
        ];
        return $table
            ->columns($columns)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListSep12Fields::route('/'),
            'create' => Pages\CreateSep12Field::route('/create'),
            'edit' => Pages\EditSep12Field::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('sep12_lang.entity.field.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep12_lang.entity.field.names');
    }

    /**
     * Creates the field type select form component.
     *
     * @param Form $form The form to create the field for.
     * @return Field The created field object.
     */
    private static function createTypeField(Form $form): Select
    {
        return Select::make('type')
            ->label(__('shared_lang.label.type'))
            ->live()
            ->required()
            ->default('string')
            ->options([
                'string' => __("sep12_lang.label.field.type.string"),
                'binary' => __("sep12_lang.label.field.type.binary"),
                'date' => __("sep12_lang.label.field.type.date"),
                'number' => __("sep12_lang.label.field.type.number"),
            ]);
    }

    /**
     * Creates the string type field possible choices form select component.
     * @param Form $form
     * @return Select
     */
    private static function createChoicesField(Form $form): Select
    {
        return Select::make('choices')
            ->multiple()
            ->label(__('sep12_lang.label.field.choices'))
            ->createOptionForm([
                TextInput::make('choice')
                    ->label(__("shared_lang.label.name"))
                    ->required()
            ])
            ->createOptionUsing(function (array $data) {
                return $data['choice'];
            })
            ->hidden(function (Get $get) use ($form): bool {
                $record = $form->getRecord();
                if ($record == null) {
                    return false;
                }

                return $get("type") != 'string';
            })
            ->options(function (?Sep12Field $record) {
                $choicesStr = $record?->choices;
                if ($choicesStr != null) {
                    $choices = explode(',', $choicesStr);
                    return array_combine($choices, $choices);
                }

                return null;
            });
    }
}
