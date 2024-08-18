<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnchorAssetResource\Actions\ViewAnchorAsset;
use App\Filament\Resources\Sep12CustomerResource\Actions\ViewSep12Customer;
use App\Filament\Resources\Sep12CustomerResource\Pages;
use App\Filament\Resources\Sep12CustomerResource\Util\Sep12CustomerResourceHelper;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12TypeToFields;
use App\Stellar\Sep12Customer\Sep12CustomerType;
use App\Stellar\Sep12Customer\Sep12Helper;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class Sep12CustomerResource extends Resource
{
    public const CUSTOM_FIELD_PREFIX = 'custom_';
    public const CUSTOM_STATUS_FIELD_SUFFIX = '_status';
    public const KYC_FIELD_WITHOUT_STATUS = ['id_type' => true];

    protected static ?string $model = Sep12Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $type = Sep12CustomerType::DEFAULT;
//        if ($customer !== null) {
//            $type = $customer->type;
//        }
        $sep12FieldsForType = Sep12Helper::getSep12FieldsForCustomerType($type);

        $requiredFildsList = $sep12FieldsForType['required'] ?? [];
        $optionalFildsList = $sep12FieldsForType['optional'] ?? [];


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

        $providedFields = Sep12CustomerResourceHelper::getCustomerCustomFormFields($requiredFildsList, true);
        $optionalFormControls = Sep12CustomerResourceHelper::getCustomerCustomFormFields($optionalFildsList, false);
        $providedFields = array_merge($providedFields, $optionalFormControls);

        $components[] = Fieldset::make(__('sep12_lang.label.provided_fields'))->schema($providedFields);
        $components[] = ResourceUtil::getModelTimestampFormControls(1);

        return $form->schema($components);
    }

    private static function createCustomerStatusField(string $name): Field {
        return Select::make($name)
            ->label(__('shared_lang.label.status'))
            ->live()
            ->afterStateUpdated(function (Set $set, $state, Sep12Customer $customer) {
                Sep12CustomerResourceHelper::onCustomerStatusChanged($state, $set, $customer);
            })
            ->required()
            ->default(CustomerStatus::PROCESSING)
            ->options([
                CustomerStatus::ACCEPTED => __("sep12_lang.label.customer.status.accepted"),
                CustomerStatus::NEEDS_INFO => __("sep12_lang.label.customer.status.needs_info"),
                CustomerStatus::PROCESSING => __("sep12_lang.label.customer.status.processing"),
                CustomerStatus::REJECTED => __("sep12_lang.label.customer.status.rejected"),
            ]);
    }




    public static function table(Table $table): Table
    {
        $columns = [
            Split::make([
                TextColumn::make('name')
                    ->searchable()
                    ->limit(35)
                    ->sortable()
                    ->description(__('shared_lang.label.name')),
                TextColumn::make('account_id')
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->iconPosition(IconPosition::After)
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->searchable()
                    ->description(__('shared_lang.label.account_id')),
                TextColumn::make('memo')
                    ->searchable()
                    ->limit(35)
                    ->description(__('shared_lang.label.memo')),
                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->description(__('shared_lang.label.status'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->description(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(function()  use ($table) {
                        $updatedAt = $table->getColumn('created_at');
                        return $updatedAt->isToggledHidden();
                    })
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->description(__('shared_lang.label.updated_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->hidden(function()  use ($table) {
                        $updatedAt = $table->getColumn('updated_at');
                        return $updatedAt->isToggledHidden();
                    })
                    ->dateTime()
                    ->sortable()
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
            ->recordUrl(null)
            ->recordAction(null)
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
    }   public static function getModelLabel(): string
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