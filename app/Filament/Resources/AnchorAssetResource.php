<?php


namespace App\Filament\Resources;

use App\Filament\Resources\AnchorAssetResource\Actions\ViewAnchorAsset;
use App\Filament\Resources\AnchorAssetResource\Pages;
use App\Filament\Resources\AnchorAssetResource\RelationManagers;
use App\Models\AnchorAsset;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Section as FormSection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AnchorAssetResource extends Resource
{
    protected static ?string $model = AnchorAsset::class;

    protected static ?string $navigationIcon = 'phosphor-money';

    public static function form(Form $form): Form
    {
        $schema = [
            Fieldset::make(__("asset_lang.label.sep_configuration"))
                ->columnSpan(3)
                ->schema([
                    TextInput::make('code')
                        ->label(__('asset_lang.label.code'))
                        ->required(),
                    TextInput::make('issuer')
                        ->label(__('asset_lang.label.issuer')),
                    Radio::make('schema')
                        ->label(__('asset_lang.label.schema'))
                        ->default('stellar')
                        ->options([
                            'stellar' => 'Stellar',
                            'iso4217' => 'iso4217',
                        ]),
                    TextInput::make('significant_decimals')
                        ->label(__('asset_lang.label.significant_decimals'))
                        ->required()
                        ->numeric()
                        ->default(2)
                ])
        ];
        $schema[] = self::getSendConfigControls();
        $schema[] = self::getDepositOrWithdrawalConfigControls(true);
        $schema[] = self::getDepositOrWithdrawalConfigControls(false);

        $schema[] = self::getSepConfigControls();
        return $form
            ->columns(3)
            ->schema($schema);
    }

    private static function getDepositOrWithdrawalConfigControls(bool $isDeposit): FormSection
    {
        $replacement = $isDeposit ? "deposit" : "withdrawal";
        $schema = [
            TextInput::make("{$replacement}_fee_fixed")
                ->label(__("asset_lang.label.{$replacement}_fee_fixed"))
                ->hidden(fn (Get $get): bool => ! $get("{$replacement}_enabled"))
                ->numeric(),
            TextInput::make("{$replacement}_fee_percent")
                ->label(__("asset_lang.label.{$replacement}_fee_percent"))
                ->hidden(fn (Get $get): bool => ! $get("{$replacement}_enabled"))
                ->numeric(),
            TextInput::make("{$replacement}_fee_minimum")
                ->label(__("asset_lang.label.{$replacement}_fee_minimum"))
                ->hidden(fn (Get $get): bool => ! $get("{$replacement}_enabled"))
                ->numeric(),
            TextInput::make("{$replacement}_min_amount")
                ->label(__("asset_lang.label.{$replacement}_min_amount"))
                ->hidden(fn (Get $get): bool => ! $get("{$replacement}_enabled"))
                ->numeric(),
            TextInput::make("{$replacement}_max_amount")
                ->label(__("asset_lang.label.{$replacement}_max_amount"))
                ->hidden(fn (Get $get): bool => ! $get("{$replacement}_enabled"))
                ->numeric(),
        ];
        return Section::make([
            Section::make([Toggle::make("{$replacement}_enabled")
                ->live()
                ->label(__("asset_lang.label.{$replacement}_enabled"))]),
            Fieldset::make(__("asset_lang.label.{$replacement}_settings"))
                ->columns(1)
                ->columnSpan(1)
                ->schema($schema)
        ])->columnSpan(1)
            ->extraAttributes(['style' => 'background-color: transparent;']);
    }

    private static function getSendConfigControls(): Fieldset
    {
        $schema = [
            TextInput::make('send_fee_fixed')
                ->label(__('asset_lang.label.send_fee_fixed'))
                ->numeric(),
            TextInput::make('send_fee_percent')
                ->label(__('asset_lang.label.send_fee_percent'))
                ->numeric(),
            TextInput::make('send_min_amount')
                ->label(__('asset_lang.label.send_min_amount'))
                ->numeric(),
            TextInput::make('send_max_amount')
                ->label(__('asset_lang.label.send_max_amount'))
                ->numeric(),
        ];
        return Fieldset::make(__("asset_lang.label.send_configuration"))
            ->columns(1)
            ->columnSpan(1)
            ->schema($schema);
    }

    private static function getSepConfigControls(): Fieldset
    {
        $schema = [
            Toggle::make("sep06_enabled")
                ->label(__("asset_lang.label.sep06_enabled")),
            Toggle::make("sep24_enabled")
                ->label(__("asset_lang.label.sep24_enabled")),
            Toggle::make("sep31_enabled")
                ->live()
                ->label(__("asset_lang.label.sep31_enabled")),
            self::getSep31ConfigControls(),
            Toggle::make("sep38_enabled")
                ->live()
                ->label(__("asset_lang.label.sep38_enabled")),
            self::getSep38ConfigControls()
        ];
        return Fieldset::make(__("asset_lang.label.sep_configuration"))
            ->columns(1)
            //->columnSpan(3)
            ->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $columns = [Split::make([
            TextColumn::make('code')
                ->description(__('asset_lang.label.code'))
                ->searchable(),
            TextColumn::make('issuer')
                ->description(__('asset_lang.label.issuer'))
                ->limit(20)
                ->searchable(),
            Stack::make([
                TextColumn::make('deposit_enabled')
                    ->icon(fn(AnchorAsset $record): ?string => $record->deposit_enabled ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
                    ->getStateUsing(function (){
                        return __('asset_lang.label.deposit_enabled');
                    }),
                TextColumn::make('withdrawal_enabled')
                    ->icon(fn(AnchorAsset $record): ?string => $record->withdrawal_enabled ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
                    ->getStateUsing(function (){
                        return __('asset_lang.label.withdrawal_enabled');
                    })
            ]),
            Stack::make([
                TextColumn::make('sep06_enabled')
                    ->icon(fn(AnchorAsset $record): ?string => $record->sep06_enabled ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
                    ->getStateUsing(function (){
                        return __('asset_lang.label.sep06_enabled');
                    }),
                TextColumn::make('sep24_enabled')
                    ->icon(fn(AnchorAsset $record): ?string => $record->withdrawal_enabled ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
                    ->getStateUsing(function (){
                        return __('asset_lang.label.sep24_enabled');
                    }),
                TextColumn::make('sep31_enabled')
                    ->icon(fn(AnchorAsset $record): ?string => $record->withdrawal_enabled ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
                    ->getStateUsing(function (){
                        return __('asset_lang.label.sep31_enabled');
                    }),
                TextColumn::make('sep38_enabled')
                    ->icon(fn(AnchorAsset $record): ?string => $record->withdrawal_enabled ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
                    ->getStateUsing(function (){
                        return __('asset_lang.label.sep38_enabled');
                    })
            ])
        ])];
        return $table
            ->columns($columns)
            ->filters([
                //
            ])
            ->actions([
                ViewAnchorAsset::make(),
                //Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->searchable()
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

    private static function getSep31ConfigControls(): Fieldset
    {
        $schema = [];
        $schema[] = Toggle::make("sep31_cfg_quotes_supported")
            ->label(__("asset_lang.label.sep31_configuration.quotes_supported"))
            ->required();
        $schema[] = Toggle::make("sep31_cfg_quotes_required")
            ->label(__("asset_lang.label.sep31_configuration.quotes_required"))
            ->required();

        $schema[] = Repeater::make('sep31_cfg_sep12_sender_types')
            ->label(__("asset_lang.label.sep31_configuration.sep12_sender_types"))
            ->schema([
                TextInput::make('name')
                    ->label(__("shared_lang.label.name"))
                    ->required(),
                Textarea::make('description')
                    ->label(__("shared_lang.label.description"))
                    ->rows(3)
                    ->required(),
            ])
            ->columns(2);

        $schema[] = Repeater::make('sep31_cfg_sep12_receiver_types')
            ->label(__("asset_lang.label.sep31_configuration.sep12_receiver_types"))
            ->schema([
                TextInput::make('name')
                    ->label(__("shared_lang.label.name"))
                    ->required(),
                Textarea::make('description')
                    ->label(__("shared_lang.label.description"))
                    ->rows(3)
                    ->required(),
            ])
            ->columns(2);

        return Fieldset::make(__("asset_lang.label.sep31_configuration"))
            ->columns(1)
            ->columnSpan(1)
            ->hidden(fn (Get $get): bool => ! $get("sep31_enabled"))
            ->schema($schema);
    }

    private static function getSep38ConfigControls(): Fieldset
    {
        $schema = [];
        //TODO Do we need decimals as SEP-38 config
        /*$schema[] = TextInput::make('sep38_cfg_decimals')
            ->label(__('shared_lang.label.decimals'))
            ->required()
            ->numeric()
            ->default(2);*/
        //TODO validate country_codes
        $schema[] = Select::make('sep38_cfg_country_codes')
            ->label(__("asset_lang.label.sep38_configuration.country_codes"))
            ->createOptionUsing(function (array $data) {
                $countryCode = $data['name'];
                return strtoupper($countryCode);
            })
            ->multiple()
            ->createOptionForm([
                TextInput::make('name')
                ->label(__("shared_lang.label.name"))
                ->required()
            ]);

        $schema[] = Repeater::make('sep38_cfg_sell_delivery_methods')
            ->label(__("asset_lang.label.sep38_configuration.sell_delivery_methods"))
            ->schema([
                TextInput::make('name')
                    ->label(__("shared_lang.label.name"))
                    ->required(),
                Textarea::make('description')
                    ->label(__("shared_lang.label.description"))
                    ->rows(3)
                    ->required(),
            ])
            ->columns(2);

        $schema[] = Repeater::make('sep38_cfg_buy_delivery_methods')
            ->label(__("asset_lang.label.sep38_configuration.buy_delivery_methods"))
            ->schema([
                TextInput::make('name')
                    ->label(__("shared_lang.label.name"))
                    ->required(),
                Textarea::make('description')
                    ->label(__("shared_lang.label.description"))
                    ->rows(3)
                    ->required(),
            ])
            ->columns(2);



        return Fieldset::make(__("asset_lang.label.sep38_configuration"))
            ->columns(1)
            ->columnSpan(1)
            ->hidden(fn (Get $get): bool => ! $get("sep38_enabled"))
            ->schema($schema);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnchorAssets::route('/'),
            'create' => Pages\CreateAnchorAsset::route('/create'),
            'edit' => Pages\EditAnchorAsset::route('/{record}/edit')
        ];
    }
}
