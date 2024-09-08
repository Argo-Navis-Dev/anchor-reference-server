<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources;

use App\Filament\Resources\AnchorAssetResource\Actions\ViewAnchorAsset;
use App\Filament\Resources\AnchorAssetResource\Pages;
use App\Models\AnchorAsset;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

/**
 * The UI. components definitions for an Anchor asset record from the database.
 */
class AnchorAssetResource extends Resource
{
    protected static ?string $model = AnchorAsset::class;

    protected static ?string $navigationIcon = 'phosphor-money';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        $schema = [
            Fieldset::make(__("asset_lang.label.sep_configuration"))
                ->columnSpan(3)
                ->columns(3)
                ->schema([
                    TextInput::make('issuer')
                        ->label(__('asset_lang.label.issuer'))
                        ->columnSpanFull()
                        ->live()
                        ->required(fn (Get $get): bool => $get('schema') == 'stellar' && $get('code') != 'native')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                $code = $get('code');
                                $schema = $get('schema');
                                $issuer = $get('issuer');
                                try {
                                    new IdentificationFormatAsset($schema, $code, $issuer);
                                } catch (InvalidAsset $ex) {
                                    LOG::error($ex->getMessage());
                                    $fail(__(
                                        'asset_lang.error.incorrect_asset_format',
                                        ['exception' => $ex->getMessage()]
                                    ));
                                }
                            }
                        ])
                        ->minLength(56)
                        ->maxLength(56),
                    Radio::make('schema')
                        ->label(__('asset_lang.label.schema'))
                        ->default('stellar')
                        ->required()
                        ->live()
                        ->options([
                            'stellar' => 'Stellar',
                            'iso4217' => 'iso4217',
                        ]),
                    TextInput::make('code')
                        ->label(__('asset_lang.label.code'))
                        ->minLength(3)
                        ->maxLength(12)
                        ->columnSpan(1)
                        ->required(),
                    TextInput::make('significant_decimals')
                        ->label(__('asset_lang.label.significant_decimals'))
                        ->required()
                        ->numeric()
                        ->maxLength(1)
                        ->default(2)
                ])
        ];
        $schema[] = self::getSendConfigControls();
        $schema[] = self::getDepositOrWithdrawalConfigControls(true);
        $schema[] = self::getDepositOrWithdrawalConfigControls(false);

        $schema[] = self::getSepConfigControls();
        $schema[] = ResourceUtil::getModelTimestampFormControls(1);
        return $form
            ->columns(3)
            ->schema($schema);
    }

    private static function getSendConfigControls(): Fieldset
    {
        $schema = [
            TextInput::make('send_fee_fixed')
                ->label(__('asset_lang.label.send_fee_fixed'))
                ->minValue(0)
                ->numeric(),
            TextInput::make('send_fee_percent')
                ->label(__('asset_lang.label.send_fee_percent'))
                ->minValue(0)
                ->maxValue(100)
                ->numeric(),
            TextInput::make('send_min_amount')
                ->label(__('asset_lang.label.send_min_amount'))
                ->minValue(0)
                ->numeric(),
            TextInput::make('send_max_amount')
                ->label(__('asset_lang.label.send_max_amount'))
                ->minValue(0)
                ->numeric(),
        ];
        return Fieldset::make(__("asset_lang.label.send_configuration"))
            ->columns(1)
            ->extraAttributes(['style' => 'margin-top: 120px;'])
            ->columnSpan(1)
            ->schema($schema);
    }

    private static function getDepositOrWithdrawalConfigControls(bool $isDeposit): FormSection
    {
        $replacement = $isDeposit ? "deposit" : "withdrawal";
        $schema = [
            TextInput::make("{$replacement}_fee_fixed")
                ->label(__("asset_lang.label.{$replacement}_fee_fixed"))
                ->minValue(0)
                ->numeric(),
            TextInput::make("{$replacement}_fee_percent")
                ->label(__("asset_lang.label.{$replacement}_fee_percent"))
                ->minValue(0)
                ->maxValue(100)
                ->numeric(),
            TextInput::make("{$replacement}_fee_minimum")
                ->label(__("asset_lang.label.{$replacement}_fee_minimum"))
                ->minValue(0)
                ->numeric(),
            TextInput::make("{$replacement}_min_amount")
                ->label(__("asset_lang.label.{$replacement}_min_amount"))
                ->minValue(0)
                ->numeric(),
            TextInput::make("{$replacement}_max_amount")
                ->label(__("asset_lang.label.{$replacement}_max_amount"))
                ->minValue(0)
                ->numeric(),
        ];
        return Section::make([
            Section::make([
                Toggle::make("{$replacement}_enabled")
                    ->label(__("asset_lang.label.{$replacement}_enabled"))
            ]),
            Fieldset::make(__("asset_lang.label.{$replacement}_settings"))
                ->columns(1)
                ->columnSpan(1)
                ->schema($schema)
        ])->columnSpan(1)
            ->extraAttributes(['style' => 'background-color: transparent; box-shadow: none !important']);
    }

    private static function getSepConfigControls(): Fieldset
    {
        $schema = [
            Toggle::make("sep06_enabled")
                ->label(__("asset_lang.label.sep06_enabled")),
            self::getSep06ConfigControls(),
            Toggle::make("sep24_enabled")
                ->label(__("asset_lang.label.sep24_enabled")),
            Toggle::make("sep31_enabled")
                ->label(__("asset_lang.label.sep31_enabled")),
            self::getSep31ConfigControls(),
            Toggle::make("sep38_enabled")
                ->label(__("asset_lang.label.sep38_enabled")),
            self::getSep38ConfigControls()
        ];
        return Fieldset::make(__("asset_lang.label.sep_configuration"))
            ->columns(1)
            ->schema($schema);
    }

    private static function getSep06ConfigControls(): Fieldset
    {
        $schema = [
            Toggle::make("sep06_deposit_exchange_enabled")
                ->label(__("asset_lang.label.sep06_deposit_exchange_enabled")),
            Select::make('sep06_deposit_methods')
                ->label(__("asset_lang.label.sep06_deposit_methods"))
                ->multiple()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label(__("shared_lang.label.name"))
                        ->required()
                ])
                ->createOptionUsing(function (array $data) {
                    return $data['name'];
                })
                ->options([
                    'wire' => __('asset_lang.label.sep06_deposit_method.wire'),
                    'cash' => __('asset_lang.label.sep06_deposit_method.cash'),
                    'mobile' => __('asset_lang.label.sep06_deposit_method.mobile')
                ]),

            Toggle::make("sep06_withdraw_exchange_enabled")
                ->label(__("asset_lang.label.sep06_withdraw_exchange_enabled")),
            Select::make('sep06_withdraw_methods')
                ->label(__("asset_lang.label.sep06_withdraw_methods"))
                ->multiple()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label(__("shared_lang.label.name"))
                        ->required()
                ])
                ->createOptionUsing(function (array $data) {
                    return $data['name'];
                })
                ->options([
                    'wire' => __('asset_lang.label.sep06_withdraw_methods.wire'),
                    'cash' => __('asset_lang.label.sep06_withdraw_methods.cash'),
                    'mobile' => __('asset_lang.label.sep06_withdraw_methods.mobile')
                ])
        ];
        return Fieldset::make(__("asset_lang.label.sep06_configuration"))
            ->columns(2)
            ->columnSpan(1)
            ->schema($schema);
    }

    private static function getSep31ConfigControls(): Fieldset
    {
        $schema = [];
        $schema[] = Toggle::make("sep31_cfg_quotes_supported")
            ->label(__("asset_lang.label.sep31_configuration.quotes_supported"));
        $schema[] = Toggle::make("sep31_cfg_quotes_required")
            ->label(__("asset_lang.label.sep31_configuration.quotes_required"))
            ->hidden(fn (Get $get): bool => !$get("sep31_cfg_quotes_supported"));

        $schema[] = Repeater::make('sep31_cfg_sep12_sender_types')
            ->defaultItems(0)
            ->label(__("asset_lang.label.sep31_configuration.sep12_sender_types"))
            ->addActionLabel(__(
                'shared_lang.label.add_entity',
                ['entity' => __('asset_lang.label.sep31_configuration.sep12_sender_types')]
            ))
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
            ->defaultItems(0)
            ->label(__("asset_lang.label.sep31_configuration.sep12_receiver_types"))
            ->addActionLabel(__(
                'shared_lang.label.add_entity',
                ['entity' => __('asset_lang.label.sep31_configuration.sep12_receiver_types')]
            ))
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
            ->schema($schema);
    }

    private static function getSep38ConfigControls(): Fieldset
    {
        $schema = [];
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
                    ->maxLength(2)
                    ->minLength(2)
                    ->required()
            ]);

        $schema[] = Repeater::make('sep38_cfg_sell_delivery_methods')
            ->defaultItems(0)
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
            ->defaultItems(0)
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
            ->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            Split::make([
                TextColumn::make('code')
                    ->description(__('asset_lang.label.code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('issuer')
                    ->description(__('asset_lang.label.issuer'))
                    ->copyable()
                    ->icon('phosphor-copy')
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->searchable(),
                Stack::make([
                    TextColumn::make('deposit_enabled')
                        ->icon(fn (
                            AnchorAsset $record
                        ): ?string => $record->deposit_enabled ? 'heroicon-c-check' : 'heroicon-o-x-mark')
                        ->getStateUsing(function () {
                            return __('asset_lang.label.deposit_enabled');
                        }),
                    TextColumn::make('withdrawal_enabled')
                        ->icon(fn (
                            AnchorAsset $record
                        ): ?string => $record->withdrawal_enabled ? 'heroicon-c-check' : 'heroicon-o-x-mark')
                        ->getStateUsing(function () {
                            return __('asset_lang.label.withdrawal_enabled');
                        })
                ]),
                Stack::make([
                    TextColumn::make('sep06_enabled')
                        ->icon(fn (
                            AnchorAsset $record
                        ): ?string => $record->sep06_enabled ? 'heroicon-c-check' : 'heroicon-o-x-mark')
                        ->getStateUsing(function () {
                            return __('asset_lang.label.sep06_enabled');
                        }),
                    TextColumn::make('sep24_enabled')
                        ->icon(fn (
                            AnchorAsset $record
                        ): ?string => $record->withdrawal_enabled ? 'heroicon-c-check' : 'heroicon-o-x-mark')
                        ->getStateUsing(function () {
                            return __('asset_lang.label.sep24_enabled');
                        }),
                    TextColumn::make('sep31_enabled')
                        ->icon(fn (
                            AnchorAsset $record
                        ): ?string => $record->withdrawal_enabled ? 'heroicon-c-check' : 'heroicon-o-x-mark')
                        ->getStateUsing(function () {
                            return __('asset_lang.label.sep31_enabled');
                        }),
                    TextColumn::make('sep38_enabled')
                        ->icon(fn (
                            AnchorAsset $record
                        ): ?string => $record->withdrawal_enabled ? 'heroicon-c-check' : 'heroicon-o-x-mark')
                        ->getStateUsing(function () {
                            return __('asset_lang.label.sep38_enabled');
                        })
                ])
            ])
        ];
        return $table
            ->columns($columns)
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make(),
                ViewAnchorAsset::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnchorAssets::route('/'),
            'create' => Pages\CreateAnchorAsset::route('/create'),
            'edit' => Pages\EditAnchorAsset::route('/{record}/edit')
        ];
    }

    public static function getModelLabel(): string
    {
        return __('asset_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('asset_lang.entity.names');
    }
}
