<?php

namespace App\Filament\Resources;

use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\Sep24TransactionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class Sep06And24ResourceUtil
{

    public static function getFormControls(bool $isSep06): array
    {
        return [
            TextInput::make('id')
                ->label(__('shared_lang.label.id'))
                ->readOnly(),
            self::getStatusFormControl($isSep06),

            TextInput::make('from_account')
                ->label(__('sep06_lang.label.from_account')),
            TextInput::make('to_account')
                ->label(__('sep06_lang.label.to_account')),

            TextInput::make('stellar_transaction_id')
                ->label(__('sep06_lang.label.stellar_transaction_id')),
            TextInput::make('external_transaction_id')
                ->label(__('sep06_lang.label.external_transaction_id')),

            TextInput::make('status_eta')
                ->label(__('sep06_lang.label.status_eta'))
                ->numeric(),
            self::getKindFormControl(),

            TextInput::make('request_asset_code')
                ->label(__('sep06_lang.label.request_asset_code')),
            TextInput::make('request_asset_issuer')
                ->label(__('sep06_lang.label.request_asset_issuer')),

            TextInput::make('type')
                ->label(__('sep06_lang.label.type'))
                ->hidden($isSep06 == false)
                ->required(),
            TextInput::make('more_info_url')
                ->label(__('sep06_lang.label.more_info_url')),

            TextInput::make('memo')
                ->label(__('sep06_lang.label.memo')),
            TextInput::make('memo_type')
                ->label(__('sep06_lang.label.memo_type')),

            TextInput::make('withdraw_anchor_account')
                ->label(__('sep06_lang.label.withdraw_anchor_account')),
            TextInput::make('quote_id')
                ->label(__('sep06_lang.label.quote_id')),

            TextInput::make('client_domain')
                ->label(__('sep06_lang.label.client_domain')),
            TextInput::make('client_name')
                ->hidden($isSep06 == false)
                ->label(__('sep06_lang.label.client_name')),

            Toggle::make('claimable_balance_supported')
                ->live()
                ->columnSpan(fn (Get $get): int => $get("claimable_balance_supported") ? 1 : 2)
                ->label(__('sep06_lang.label.claimable_balance_supported'))
                ->required(),
            TextInput::make('claimable_balance_id')
                ->hidden(fn (Get $get): bool => ! $get("claimable_balance_supported"))
                ->label(__('sep06_lang.label.claimable_balance_id')),

            TextInput::make('source_asset')
                ->hidden($isSep06)
                ->label(__('sep24_lang.label.source_asset')),
            TextInput::make('destination_asset')
                ->hidden($isSep06)
                ->label(__('sep24_lang.label.destination_asset')),

            TextInput::make('muxed_account')
                ->hidden($isSep06)
                ->label(__('sep24_lang.label.muxed_account')),
            TextInput::make('status_message')
                ->hidden($isSep06)
                ->label(__('sep24_lang.label.status_message')),
            ResourceUtil::getTransactionTimestampFormControls(),
            ResourceUtil::getAmountInfoFormControls(),
            Fieldset::make(__("sep06_lang.label.sep10_account_info"))
                ->columns(2)
                ->schema([
                    TextInput::make('sep10_account')
                        ->label(__('sep06_lang.label.sep10_account')),
                    TextInput::make('sep10_account_memo')
                        ->label(__('sep06_lang.label.sep10_account_memo')),
                ]),

            ResourceUtil::getRefundsInfoFormControls(!$isSep06),
            Fieldset::make(__("sep06_lang.label.missing_info_errors"))
                ->columns(2)
                ->hidden($isSep06 == false)
                ->schema([
                    Textarea::make('required_info_message')
                        ->label(__('sep06_lang.label.required_info_message')),
                    Textarea::make('required_info_updates')
                        ->label(__('sep06_lang.label.required_info_updates')),
                    Textarea::make('required_customer_info_message')
                        ->label(__('sep06_lang.label.required_customer_info_message')),
                    Textarea::make('required_customer_info_updates')
                        ->label(__('sep06_lang.label.required_customer_info_updates'))
                ]),

            Section::make(__('sep06_lang.label.fee_details'))
                ->hidden($isSep06 == false)
                ->schema([
                    Textarea::make('fee_details')
                        ->hiddenLabel(true)
                        ->columnSpanFull(),
                ]),
            Section::make(__('sep06_lang.label.instructions'))
                ->description(__('sep06_lang.label.instructions.description'))
                ->hidden($isSep06 == false)
                ->schema([
                    Textarea::make('instructions')
                        ->hiddenLabel(true)
                        ->columnSpanFull(),
                ]),
            Textarea::make('message')
                ->hidden($isSep06 == false)
                ->label(__('sep06_lang.label.message'))
                ->columnSpanFull(),
            Textarea::make('stellar_transactions')
                ->label(__('sep06_lang.label.stellar_transactions'))
                ->columnSpanFull(),
            ResourceUtil::getModelTimestampFormControls(1)
        ];
    }

    private static function getStatusFormControl(bool $isSep06): Select {
        return Select::make('status')
            ->label(__('shared_lang.label.status'))
            ->options($isSep06 ? self::getSep06StatusOptions() : self::getSep24StatusOptions());
    }

    private static function getSep06StatusOptions(): array {
        return [
            Sep06TransactionStatus::COMPLETED => __('sep06_lang.label.status.completed'),
            Sep06TransactionStatus::PENDING_EXTERNAL => __('sep06_lang.label.status.pending_external'),
            Sep06TransactionStatus::PENDING_ANCHOR => __('sep06_lang.label.status.pending_external'),
            Sep06TransactionStatus::PENDING_STELLAR => __('sep06_lang.label.status.pending_external'),
            Sep06TransactionStatus::PENDING_TRUST => __('sep06_lang.label.status.pending_trust'),
            Sep06TransactionStatus::PENDING_USER  => __('sep06_lang.label.status.pending_user'),
            Sep06TransactionStatus::PENDING_USER_TRANSFER_START  => __('sep06_lang.label.status.pending_user_transfer_start'),
            Sep06TransactionStatus::PENDING_USER_TRANSFER_COMPLETE  => __('sep06_lang.label.status.pending_user_transfer_complete'),
            Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE  => __('sep06_lang.label.status.pending_customer_info_update'),
            Sep06TransactionStatus::PENDING_TRANSACTION_INFO_UPDATE  => __('sep06_lang.label.status.pending_transaction_info_update'),
            Sep06TransactionStatus::INCOMPLETE  => __('sep06_lang.label.status.incomplete'),
            Sep06TransactionStatus::EXPIRED  => __('sep06_lang.label.status.expired'),
            Sep06TransactionStatus::NO_MARKET  => __('sep06_lang.label.status.no_market'),
            Sep06TransactionStatus::TOO_SMALL  => __('sep06_lang.label.status.too_small'),
            Sep06TransactionStatus::TOO_LARGE  => __('sep06_lang.label.status.too_large'),
            Sep06TransactionStatus::ERROR  => __('sep06_lang.label.status.error'),
            Sep06TransactionStatus::REFUNDED => __('sep06_lang.label.status.refunded')
        ];
    }
    private static function getSep24StatusOptions(): array {
        return [
            Sep24TransactionStatus::INCOMPLETE => __('sep24_lang.label.status.incomplete'),
            Sep24TransactionStatus::PENDING_USER_TRANSFER_START => __('sep24_lang.label.status.pending_user_transfer_start'),
            Sep24TransactionStatus::PENDING_USER_TRANSFER_COMPLETE => __('sep24_lang.label.status.pending_user_transfer_complete'),
            Sep24TransactionStatus::PENDING_EXTERNAL => __('sep24_lang.label.status.pending_external'),
            Sep24TransactionStatus::PENDING_ANCHOR => __('sep24_lang.label.status.pending_anchor'),
            Sep24TransactionStatus::PENDING_STELLAR => __('sep24_lang.label.status.pending_stellar'),
            Sep24TransactionStatus::PENDING_TRUST => __('sep24_lang.label.status.pending_trust'),
            Sep24TransactionStatus::PENDING_USER => __('sep24_lang.label.status.pending_user'),
            Sep24TransactionStatus::COMPLETED => __('sep24_lang.label.status.completed'),
            Sep24TransactionStatus::REFUNDED => __('sep24_lang.label.status.refunded'),
            Sep24TransactionStatus::EXPIRED => __('sep24_lang.label.status.expired'),
            Sep24TransactionStatus::NO_MARKET => __('sep24_lang.label.status.no_market'),
            Sep24TransactionStatus::TOO_SMALL => __('sep24_lang.label.status.too_small'),
            Sep24TransactionStatus::TOO_LARGE => __('sep24_lang.label.status.too_large'),
            Sep24TransactionStatus::ERROR => __('sep24_lang.label.status.error'),
        ];
    }
    private static function getKindFormControl(): Select {
        return Select::make('kind')
            ->label(__('sep06_lang.label.kind'))
            ->options([
                'deposit' => __('sep06_lang.label.kind.deposit'),
                'deposit-exchange' => __('sep06_lang.label.kind.deposit_exchange'),
                'withdrawal' => __('sep06_lang.label.kind.withdrawal'),
                'withdrawal-exchange' => __('sep06_lang.label.kind.withdrawal_exchange')
            ]);
    }

    public static function getTableColumns(bool $isSep06): array
    {
        $columns = [
            Split::make([
                TextColumn::make('from_account')
                    ->description(__('sep06_lang.label.from_account'))
                    ->default('-')
                    ->searchable(),
                TextColumn::make('to_account')
                    ->description(__('sep06_lang.label.to_account'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(__('sep12_lang.kyc.status'))
                    ->searchable(),
                TextColumn::make('request_asset_code')
                    ->description(__('sep06_lang.label.request_asset_code'))
                    ->searchable(),
                TextColumn::make('type')
                    ->description(__('sep06_lang.label.type'))
                    ->hidden($isSep06 == false)
                    ->searchable(),
                TextColumn::make('kind')
                    ->description(__('sep06_lang.label.kind'))
                    ->searchable(),
                TextColumn::make('transfer_received_at')
                    ->description(__('sep06_lang.label.transfer_received_at'))
                    ->searchable(),
            ])
        ];

        $firstStackFields = ResourceUtil::getAmountInfoTableFields();
        $firstStackFields[] = TextColumn::make('claimable_balance_supported')
              ->icon(fn(Model $record): ?string => $record->claimable_balance_supported ? 'heroicon-m-check-circle' : 'heroicon-s-x-circle')
              ->getStateUsing(function (){
                  return __('sep06_lang.label.claimable_balance_supported');
              });
        $columns[] = Panel::make([
            Split::make([
                Stack::make($firstStackFields),
                Stack::make(ResourceUtil::getTransactionsInfoTableFields())
            ])
        ])->collapsible();
        return $columns;
    }


}