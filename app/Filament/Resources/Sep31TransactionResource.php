<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep31TransactionResource\Pages;
use App\Filament\Resources\Sep31TransactionResource\RelationManagers;
use App\Models\Sep31Transaction;
use ArgoNavis\PhpAnchorSdk\shared\Sep31TransactionStatus;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Sep31TransactionResource extends Resource
{
    protected static ?string $model = Sep31Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-s-circle-stack';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('id')
                    ->readOnly()
                    ->label(__('shared_lang.label.id')),
                self::getStatusFormControl(),

                TextInput::make('stellar_transaction_id')
                    ->label(__('sep31_lang.label.stellar_transaction_id')),
                TextInput::make('external_transaction_id')
                    ->label(__('sep31_lang.label.external_transaction_id')),

                TextInput::make('status_eta')
                    ->label(__('sep31_lang.label.status_eta'))
                    ->numeric(),
                TextInput::make('stellar_account_id')
                    ->label(__('sep31_lang.label.stellar_account_id')),

                TextInput::make('sender_id')
                    ->label(__('sep31_lang.label.sender_id')),
                TextInput::make('receiver_id')
                    ->label(__('sep31_lang.label.receiver_id')),

                TextInput::make('stellar_memo')
                    ->label(__('sep31_lang.label.stellar_memo')),
                TextInput::make('stellar_memo_typ')
                    ->label(__('sep31_lang.label.stellar_memo_typ')),

                TextInput::make('sep10_account')
                    ->label(__('sep31_lang.label.sep10_account')),
                TextInput::make('sep10_account_memo')
                    ->label(__('sep31_lang.label.sep10_account_memo')),

                TextInput::make('quote_id')
                    ->label(__('sep31_lang.label.quote_id')),
                TextInput::make('callback_url')
                    ->label(__('sep31_lang.label.callback_url')),

                TextInput::make('client_domain')
                    ->columnSpan(2)
                    ->label(__('sep31_lang.label.client_domain')),

                ResourceUtil::getAmountInfoFormControls(),
                ResourceUtil::getTransactionTimestampFormControls(),
                ResourceUtil::getRefundsInfoFormControls(false),
                Textarea::make('stellar_transactions')
                    ->label(__('sep31_lang.label.stellar_transactions'))
                    ->columnSpanFull(),
                Textarea::make('fee_details')
                    ->label(__('sep31_lang.label.fee_details'))
                    ->columnSpanFull(),
                TextInput::make('required_info_message')
                    ->columnSpan(2)
                    ->label(__('sep31_lang.label.required_info_message')),
                Textarea::make('required_customer_info_updates')
                    ->label(__('sep31_lang.label.required_customer_info_updates'))
                    ->columnSpanFull(),
                Textarea::make('message')
                    ->label(__('sep31_lang.label.message'))
                    ->columnSpanFull(),
                ResourceUtil::getModelTimestampFormControls(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            Split::make([
                TextColumn::make('sender_id')
                    ->description(__('sep31_lang.label.sender_id'))
                    ->default('-')
                    ->searchable(),
                TextColumn::make('receiver_id')
                    ->description(__('sep31_lang.label.receiver_id'))
                    ->searchable(),
                TextColumn::make('amount_in_asset')
                    ->description(__('sep31_lang.label.amount_in_asset'))
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('amount_out_asset')
                    ->description(__('sep31_lang.label.amount_out_asset'))
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(__('sep31_lang.label.status'))
                    ->searchable(),
            ])
        ];

        $firstStackFields = ResourceUtil::getAmountInfoTableFields();
        $columns[] = Panel::make([
            Split::make([
                Stack::make($firstStackFields),
                Stack::make(ResourceUtil::getTransactionsInfoTableFields())
            ])
        ])->collapsible();
        return $table
            ->columns($columns)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListSep31Transactions::route('/'),
            'create' => Pages\CreateSep31Transaction::route('/create'),
            'edit' => Pages\EditSep31Transaction::route('/{record}/edit'),
        ];
    }

    private static function getStatusFormControl(): Select {
        return Select::make('status')
            ->label(__('sep31_lang.label.status'))
            ->options([
                Sep31TransactionStatus::PENDING_SENDER => __('sep31_lang.label.status.pending_sender'),
                Sep31TransactionStatus::PENDING_STELLAR => __('sep31_lang.label.status.pending_stellar'),
                Sep31TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE => __('sep31_lang.label.status.pending_customer_info_update'),
                Sep31TransactionStatus::PENDING_TRANSACTION_INFO_UPDATE => __('sep31_lang.label.status.pending_transaction_info_update'),
                Sep31TransactionStatus::PENDING_RECEIVER => __('sep31_lang.label.status.pending_receiver'),
                Sep31TransactionStatus::PENDING_EXTERNAL => __('sep31_lang.label.status.pending_external'),
                Sep31TransactionStatus::COMPLETED => __('sep31_lang.label.status.completed'),
                Sep31TransactionStatus::REFUNDED => __('sep31_lang.label.status.refunded'),
                Sep31TransactionStatus::EXPIRED => __('sep31_lang.label.status.expired'),
                Sep31TransactionStatus::ERROR => __('sep31_lang.label.status.error'),
            ]);
    }

    public static function getModelLabel(): string
    {
        return __('sep31_lang.entity.name');
    }

    public static function getPluralLabel(): string
    {
        return __('sep31_lang.entity.names');
    }
}
