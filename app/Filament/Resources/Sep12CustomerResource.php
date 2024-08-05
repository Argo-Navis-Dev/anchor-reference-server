<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnchorAssetResource\Actions\ViewAnchorAsset;
use App\Filament\Resources\Sep12CustomerResource\Actions\ViewSep12Customer;
use App\Filament\Resources\Sep12CustomerResource\Pages;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
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
    private const KYC_FIELD_WITHOUT_STATUS = ['id_type' => true];

    protected static ?string $model = Sep12Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $fields = Sep12Field::all();
        $statusField = self::createCustomerStatusField('status');
        $statusField->columnSpan(2);
        $components = [
            TextInput::make('account_id')
                ->label(__('shared_lang.label.account_id'))
                ->minLength(56)
                ->maxLength(69)
                ->required(),
            TextInput::make('memo')
                ->label(__('shared_lang.label.memo'))
                ->maxLength(255),
            $statusField,
            TextInput::make('callback_url')
                ->url(true)
                ->maxLength(2000)
                ->label(__('sep12_lang.label.callback_url')),
            TextInput::make('lang')
                ->minLength(2)
                ->maxLength(2)
                ->label(__('shared_lang.label.lang'))
        ];
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;

        $providedFields = [];
        foreach ($fields as $field) {
            $fieldType = $field->type;
            $name = "{$customFieldPrefix}{$field->id}";
            //TODO Avoid hard coding different field types
            $label = __("sep12_lang.label.{$field->key}");
            $hasStatus = !isset(self::KYC_FIELD_WITHOUT_STATUS[$field->key]);
            if ($fieldType == 'string') {
                if ($field->choices != null) {
                    $providedFields[] = self::createDynamicSelectField($field, $hasStatus);
                } else {
                    $providedFields[] = TextInput::make(name: $name)
                        ->label($label);
                }
            }
            if ($fieldType == 'binary') {
                $providedFields[] = self::getBinaryFieldComponent($field->id, $label);
            }
            $statusFieldName = "{$customFieldPrefix}{$field->id}{$statusSuffix}";
            if ($hasStatus) {
                $requiresVerification = $field->requires_verification;
                $statusField = self::createProvidedFieldStatusComp($statusFieldName, $requiresVerification);
                $providedFields[] = $statusField;
            }
        }
        $components[] = Fieldset::make(__('sep12_lang.label.provided_fields'))->schema($providedFields);
        $components[] = ResourceUtil::getModelTimestampFormControls(1);

        return $form->schema($components);
    }

    private static function createCustomerStatusField(string $name): Field {
        return Select::make($name)
            ->label(__('shared_lang.label.status'))
            ->required()
            ->default(CustomerStatus::PROCESSING)
            ->options([
                CustomerStatus::ACCEPTED => __("sep12_lang.label.customer.status.accepted"),
                CustomerStatus::NEEDS_INFO => __("sep12_lang.label.customer.status.needs_info"),
                CustomerStatus::PROCESSING => __("sep12_lang.label.customer.status.processing"),
                CustomerStatus::REJECTED => __("sep12_lang.label.customer.status.rejected"),
            ]);
    }

    private static function createProvidedFieldStatusComp(string $name, bool $requiresVerification): Field {
        $option = [
            ProvidedCustomerFieldStatus::ACCEPTED => __('sep12_lang.label.field.status.accepted'),
            ProvidedCustomerFieldStatus::PROCESSING => __('sep12_lang.label.field.status.processing'),
            ProvidedCustomerFieldStatus::REJECTED => __('sep12_lang.label.field.status.rejected'),
        ];
        if($requiresVerification) {
            $option[ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED] = __('sep12_lang.label.field.status.verification_required');
        }
        return Select::make($name)
            ->label(__('shared_lang.label.status'))
            ->default(CustomerStatus::PROCESSING)
            ->options($option);
    }

    private static function createDynamicSelectField(Sep12Field $field, bool $hasStatusField): Select
    {
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $name = "{$customFieldPrefix}{$field->id}";
        $choices = explode(",", $field->choices);
        $options = [];
        foreach ($choices as $choice) {
            $options[$choice] = __("sep12_lang.label.{$field->key}.{$choice}");
        }
        $component = Select::make($name)
            ->label(__("sep12_lang.label.{$field->key}"))
            ->columnSpan(1)
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
                    ->limit(20)
                    ->description(__('shared_lang.label.account_id')),
                TextColumn::make('memo')
                    ->description(__('shared_lang.label.memo'))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(__('shared_lang.label.status'))
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
                ViewSep12Customer::make()
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
    public static function getModelLabel(): string
    {
        return __('sep12_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep12_lang.entity.names');
    }

    public static function canCreate(): bool
    {
        return false;
    }
}