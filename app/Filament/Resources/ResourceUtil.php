<?php

namespace App\Filament\Resources;

use App\Models\Sep12Customer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->numeric(),
                TextInput::make('amount_out')
                    ->label(__('shared_lang.label.amount_out'))
                    ->numeric(),
                TextInput::make('amount_expected')
                    ->label(__('shared_lang.label.amount_expected'))
                    ->numeric(),
                TextInput::make('amount_fee')
                    ->label(__('shared_lang.label.amount_fee'))
                    ->numeric(),

                TextInput::make('amount_in_asset')
                    ->columnSpan(4)
                    ->label(__('shared_lang.label.amount_in_asset')),
                TextInput::make('amount_out_asset')
                    ->columnSpan(4)
                    ->label(__('shared_lang.label.amount_out_asset')),
                TextInput::make('amount_fee_asset')
                    ->columnSpan(4)
                    ->label(__('shared_lang.label.amount_fee_asset')),

            ]);
    }
    
    public static function getRefundsInfoFormControls(bool $hasRefunded): Fieldset {
        $columns = $hasRefunded ? 3 : 2;
        return Fieldset::make(__("shared_lang.label.refund_info"))
            ->columns($columns)
            ->schema([
                TextInput::make('refund_memo')
                    ->label(__('shared_lang.label.refund_memo')),
                TextInput::make('refund_memo_type')
                    ->label(__('shared_lang.label.refund_memo_type')),
                Toggle::make('refunded')
                    ->label(__('shared_lang.label.refunded'))
                    ->required(),
                Textarea::make('refunds')
                    ->label(__('shared_lang.label.refunds'))
                    ->columnSpanFull(),
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
}