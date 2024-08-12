<?php

namespace App\Filament\Resources;

use App\Models\Sep12Customer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class ResourceUtil
{

    public static function getModelTimestampFormControls(int $colspan): Section {
        return Section::make()
            ->columnSpan($colspan)
            ->hidden(fn(?Model $record): ?bool => $record == null)
            ->schema([
                Placeholder::make('created_at')
                    ->label(__('shared_lang.label.created_at'))
                    ->columns(1)
                    ->content(fn(Model $record): ?string => $record->created_at?->diffForHumans()),
                Placeholder::make('updated_at')
                    ->label(__('shared_lang.label.updated_at'))
                    ->columns(1)
                    ->content(fn(Model $record): ?string => $record->updated_at?->diffForHumans())
            ]);
    }

    public static function getTransactionTimestampFormControls(): Fieldset {
        return Fieldset::make(__("shared_lang.label.tx_dates"))
            ->columns(3)
            ->schema([
                DateTimePicker::make('tx_started_at')
                    ->label(__('shared_lang.label.tx_started_at'))
                    ->required(),
                DateTimePicker::make('tx_updated_at')
                    ->label(__('shared_lang.label.tx_updated_at')),
                DateTimePicker::make('tx_completed_at')
                    ->label(__('shared_lang.label.tx_completed_at')),
                Section::make(__('shared_lang.label.transfer_received_at'))
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('transfer_received_at')
                            ->columnStart(2)
                            ->columnSpan(1)
                            ->hiddenLabel(true)
                    ])
            ]);
    }

    public static function getAmountInfoFormControls(): Fieldset {
        return Fieldset::make(__("shared_lang.label.amount_info"))
            ->columns(4)
            ->schema([
                TextInput::make('amount_in')
                    ->label(__('shared_lang.label.amount_in'))
                    ->minValue(0)
                    ->numeric(),
                TextInput::make('amount_out')
                    ->label(__('shared_lang.label.amount_out'))
                    ->minValue(0)
                    ->numeric(),
                TextInput::make('amount_expected')
                    ->label(__('shared_lang.label.amount_expected'))
                    ->minValue(0)
                    ->numeric(),
                TextInput::make('amount_fee')
                    ->label(__('shared_lang.label.amount_fee'))
                    ->minValue(0)
                    ->numeric(),

                TextInput::make('amount_in_asset')
                    ->columnSpan(1)
                    ->label(__('shared_lang.label.amount_in_asset')),
                TextInput::make('amount_out_asset')
                    ->columnSpan(1)
                    ->label(__('shared_lang.label.amount_out_asset')),
                TextInput::make('amount_fee_asset')
                    ->columnSpan(1)
                    ->label(__('shared_lang.label.amount_fee_asset')),

            ]);
    }
    
    public static function getRefundsInfoFormControls(bool $hasRefunded): Fieldset {
        $columns = $hasRefunded ? 3 : 2;
        return Fieldset::make(__("shared_lang.label.refund_info"))
            ->columns($columns)
            ->schema([
                Toggle::make('refunded')
                    ->label(__('shared_lang.label.refunded'))
                    ->columnSpanFull()
                    ->required(),
                self::getMemoTypeFormControl(true),
                TextInput::make('refund_memo')
                    ->required(fn (Get $get): bool => $get("refund_memo_type") != null)
                    ->label(__('shared_lang.label.refund_memo')),
                self::getRefundsFormControl(),
            ]);
    }

    private static function getRefundsFormControl(): Section {
        return Section::make()
            ->schema([
                TextInput::make('refunds.amount_refunded')
                    ->label(__('shared_lang.label.refunds.amount_refunded'))
                    ->numeric(),
                TextInput::make('refunds.amount_fee')
                    ->label(__('shared_lang.label.refunds.amount_fee'))
                    ->numeric(),
                Repeater::make('refunds.payments')
                    ->schema([
                        TextInput::make('id')
                            ->label(__('shared_lang.label.id')),
                        Select::make("id_type")
                            ->label(__('shared_lang.label.id_type'))
                            ->options([
                                'stellar' => 'stellar',
                                'external' => 'external'
                            ]),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(0)
                            ->label(__('shared_lang.label.amount')),
                        TextInput::make('fee')
                            ->numeric()
                            ->minValue(0)
                            ->label(__('shared_lang.label.fee'))
                    ])
                ->columns(4)
            ]);
    }

    public static function getAmountInfoTableFields(): array {
        return [
            TextColumn::make('amount_in')
                ->icon('phosphor-money')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.amount_in') . ': '. $record->amount_in;
                }),
            TextColumn::make('amount_out')
                ->icon('phosphor-money')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.amount_out') . ': '. $record->amount_out;
                }),
            TextColumn::make('amount_expected')
                ->icon('phosphor-money')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.amount_expected') . ': '. $record->amount_expected;
                }),
            TextColumn::make('amount_fee')
                ->icon('phosphor-money')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.amount_fee') . ': '. $record->amount_fee;
                }),
        ];
    }

    public static function getTransactionsInfoTableFields(): array {
        return [
            TextColumn::make('tx_started_at')
                ->icon('heroicon-o-calendar-date-range')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.tx_started_at') . ': '. $record->tx_started_at;
                }),
            TextColumn::make('tx_updated_at')
                ->icon('heroicon-o-calendar-date-range')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.tx_updated_at') . ': '. $record->tx_updated_at;
                }),
            TextColumn::make('tx_completed_at')
                ->icon('heroicon-o-calendar-date-range')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.tx_completed_at') . ': '. $record->tx_completed_at;
                }),
            TextColumn::make('transfer_received_at')
                ->icon('heroicon-o-calendar-date-range')
                ->getStateUsing(function (Model $record){
                    return __('shared_lang.label.transfer_received_at') . ': '. $record->transfer_received_at;
                }),
        ];
    }

    public static function getMemoTypeFormControl(bool $isRefund): Select {
        $options = [
            'text' => __('shared_lang.label.memo_type.text'),
            'id' => __('shared_lang.label.memo_type.id'),
            'hash' => __('shared_lang.label.memo_type.hash'),
        ];
        $name = $isRefund ? 'refund_memo_type' : 'memo_type';
        return Select::make($name)
            ->live()
            ->label($isRefund ? __('shared_lang.label.refund_memo_type') :__('shared_lang.label.memo_type'))
            ->options($options);
    }

    public static function getFeeDetailsFormControl(bool $isSep06): Section {
        $schema = [
            TextInput::make('fee_details.total')
                ->label(__("shared_lang.label.total"))
                ->numeric()
                ->minValue(0)
                ->required(),
            TextInput::make('fee_details.asset')
                ->label(__("shared_lang.label.asset"))
                ->maxLength(80)
                ->required(),
            Repeater::make('fee_details.details')
                ->schema([
                    TextInput::make('amount')
                        ->label(__("shared_lang.label.amount"))
                        ->minValue(0)
                        ->numeric()
                        ->required(),
                    TextInput::make('name')
                        ->label(__("shared_lang.label.name"))
                        ->required(),
                    TextArea::make('description')
                        ->label(__("shared_lang.label.description"))
                        ->required()
                ])
                ->columns(3)
                ->columnSpan(2)
        ];
        return Section::make(__('shared_lang.label.fee_details'))
            ->description(__('shared_lang.label.fee_details.description'))
            ->hidden(!$isSep06)
            ->columns(2)
            ->schema($schema);
    }

    public static function elideTableColumnTextInMiddle(
        string $cellValue,
        ?int $maxLength = null): string {
        if($maxLength == null) {
            $maxLength = 20;
        }
        $halfLength = intdiv($maxLength, 2);
        if (strlen($cellValue) > $maxLength) {
            return substr($cellValue, 0, $halfLength) . '...' . substr($cellValue, -$halfLength);
        }
        return $cellValue;
    }
}