<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep12CustomerResource\Pages;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class Sep12CustomerResource extends Resource
{
    public const CUSTOM_FIELD_PREFIX = 'custom_';
    public const CUSTOM_STATUS_FIELD_SUFFIX = '_status';
    private const KYC_FIELD_NO_STATUS = ['id_type' => true];
    protected static ?string $model = Sep12Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Customers';

    public static function form(Form $form): Form
    {
        $fields = Sep12Field::all();
        $statusField = self::createStatusField('status');
        $statusField->columnSpan(2);
        $components = [
            TextInput::make('account_id')
                ->label(__('shared_lang.label.account_id'))
                ->minLength(56)
                ->maxLength(56)
                ->required(),
            TextInput::make('memo')
                ->label(__('shared_lang.label.memo'))
                ->numeric(),
            $statusField,
            TextInput::make('callback_url'),
            TextInput::make('lang')
                ->required(),
        ];
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;

        $kycFields = [];
        foreach ($fields as $field) {
            $fieldType = $field->type;
            $name = "{$customFieldPrefix}{$field->id}";
            //TODO Avoid hard coding different field types
            $label = __("sep12_lang.kyc.{$field->key}");
            $hasStatus = !isset(self::KYC_FIELD_NO_STATUS[$field->key]);
            if ($fieldType == 'string') {
                if ($field->choices != null) {
                    $kycFields[] = self::createDynamicSelectField($field, $hasStatus);
                } else {
                    $kycFields[] = TextInput::make(name: $name)
                        ->label($label)
                        ->required();
                }
            }
            if ($fieldType == 'binary') {
                $kycFields[] = self::getBinaryFieldComponent($field->id, $label);
            }
            $statusFieldName = "{$customFieldPrefix}{$field->id}{$statusSuffix}";
            if ($hasStatus) {
                $statusField = self::createStatusField($statusFieldName);
                $kycFields[] = $statusField;
            }
        }
        $components[] = Fieldset::make('KYC Fields')->schema($kycFields);

        $lastEditedSection = Section::make()
            ->schema([
                Placeholder::make('created_at')
                    ->label(__('shared_lang.label.created_at'))
                    ->columns(1)
                    ->content(fn(Sep12Customer $record): ?string => $record->created_at?->diffForHumans()),
                Placeholder::make('updated_at')
                    ->label(__('shared_lang.label.updated_at'))
                    ->columns(1)
                    ->content(fn(Sep12Customer $record): ?string => $record->updated_at?->diffForHumans())
            ]);
        $lastEditedSection->columnSpan(1);
        $lastEditedSection->columns(2);
        $components[] = $lastEditedSection;

        return $form->schema($components);
    }

    private static function createStatusField(
        string $name,
        ?string $label = null,
    ): Field {
        if ($label == null) {
            $label = __("sep12_lang.kyc.status");
        }

        return Select::make($name)
            ->label($label)
            ->options([
                CustomerStatus::ACCEPTED => __("sep12_lang.kyc.status.accepted"),
                CustomerStatus::NEEDS_INFO => __("sep12_lang.kyc.status.needs_info"),
                CustomerStatus::PROCESSING => __("sep12_lang.kyc.status.processing"),
                CustomerStatus::REJECTED => __("sep12_lang.kyc.status.rejected"),
            ]);
    }

    private static function createDynamicSelectField(Sep12Field $field, bool $hasStatusField): Select
    {
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $name = "{$customFieldPrefix}{$field->id}";
        $choices = explode(",", $field->choices);
        $options = [];
        foreach ($choices as $choice) {
            $options[$choice] = __("sep12_lang.kyc.{$field->key}.{$choice}");
        }
        $component = Select::make($name)
            ->label(__("sep12_lang.kyc.{$field->key}"))
            ->columnSpan(2)
            ->options($options);
        if (!$hasStatusField) {
            $component->columnSpan(2);
        }
        return $component;
    }

    private static function getBinaryFieldComponent(
        string $fieldID,
        string $label,
    ): Placeholder {
        return Placeholder::make('Image')
            ->hidden(fn($record) => $record == null)
            ->label($label)
            ->content(function ($record) use ($fieldID): HtmlString {
                $id = $record != null ? $record->id : null;
                $src = '/customer/' . $id . '/binary-field/' . $fieldID;
                return new HtmlString("<img src= '" . $src . "')>");
            });
    }

    public static function table(Table $table): Table
    {
        $columns = [
            Split::make([
                TextColumn::make('name')
                    ->description(__('shared_lang.label.name')),
                TextColumn::make('account_id')
                    ->description(__('shared_lang.label.account_id')),
                TextColumn::make('memo')
                    ->description(__('shared_lang.label.memo'))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(__('sep12_lang.kyc.status'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->description(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->description(__('shared_lang.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
        ];
        $columns[] = Panel::make([
            Stack::make([
                TextColumn::make('email')
                    ->icon('heroicon-c-envelope'),
                TextColumn::make('idTypeWithNumber')
                    ->icon('heroicon-o-identification'),
            ]),
        ])->collapsible();

        return $table
            ->columns($columns)
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
            'index' => Pages\ListSep12Customers::route('/'),
            'create' => Pages\CreateSep12Customer::route('/create'),
            'edit' => Pages\EditSep12Customer::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}