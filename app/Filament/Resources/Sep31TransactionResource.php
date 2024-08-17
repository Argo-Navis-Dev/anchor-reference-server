<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Sep06TransactionResource\Actions\EditSep31TransactionResource;
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
use Filament\Support\Enums\IconPosition;
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
                self::getStatusFormControl(false),
                TextInput::make('stellar_transaction_id')
                    ->disabled()
                    ->label(__('sep31_lang.label.stellar_transaction_id')),
                TextInput::make('external_transaction_id')
                    ->disabled()
                    ->label(__('sep31_lang.label.external_transaction_id')),

                TextInput::make('status_eta')
                    ->label(__('sep31_lang.label.status_eta'))
                    ->disabled()
                    ->numeric(),
                TextInput::make('stellar_account_id')
                    ->disabled()
                    ->label(__('sep31_lang.label.stellar_account_id')),

                TextInput::make('sender_id')
                    ->disabled()
                    ->label(__('sep31_lang.label.sender_id')),
                TextInput::make('receiver_id')
                    ->disabled()
                    ->label(__('sep31_lang.label.receiver_id')),

                TextInput::make('stellar_memo')
                    ->disabled()
                    ->label(__('sep31_lang.label.stellar_memo')),
                TextInput::make('stellar_memo_typ')
                    ->disabled()
                    ->label(__('sep31_lang.label.stellar_memo_typ')),

                TextInput::make('sep10_account')
                    ->disabled()
                    ->label(__('sep31_lang.label.sep10_account')),
                TextInput::make('sep10_account_memo')
                    ->disabled()
                    ->label(__('sep31_lang.label.sep10_account_memo')),

                TextInput::make('quote_id')
                    ->disabled()
                    ->label(__('sep31_lang.label.quote_id')),
                TextInput::make('callback_url')
                    ->disabled()
                    ->label(__('sep31_lang.label.callback_url')),

                TextInput::make('client_domain')
                    ->disabled()
                    ->columnSpan(2)
                    ->label(__('sep31_lang.label.client_domain')),

                ResourceUtil::getAmountInfoFormControls(),
                ResourceUtil::getTransactionTimestampFormControls(),
                ResourceUtil::getRefundsInfoFormControls(false),
                ResourceUtil::getStellarTransactionsFormControl(),
                ResourceUtil::getFeeDetailsFormControl(true),
                TextArea::make('required_info_message')
                    ->disabled()
                    ->columnSpan(2)
                    ->label(__('sep31_lang.label.required_info_message')),
                Select::make('required_customer_info_updates')
                    ->multiple(true)
                    ->disabled()
                    ->label(__('sep31_lang.label.required_customer_info_updates'))
                    ->columnSpanFull(),
                Textarea::make('message')
                    ->disabled()
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
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->default('-')
                    ->searchable(),
                TextColumn::make('receiver_id')
                    ->description(__('sep31_lang.label.receiver_id'))
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->searchable(),
                TextColumn::make('amount_in_asset')
                    ->copyable()
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->icon(function(Model $model)  {
                        $fromAccount = $model['amount_in_asset'];
                        return !empty($fromAccount) ? 'phosphor-copy' : null;
                    })
                    ->description(__('sep31_lang.label.amount_in_asset'))
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('amount_out_asset')
                    ->copyable()
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->icon(function(Model $model)  {
                        $fromAccount = $model['amount_out_asset'];
                        return !empty($fromAccount) ? 'phosphor-copy' : null;
                    })
                    ->description(__('sep31_lang.label.amount_out_asset'))
                    ->formatStateUsing(function ($state) {
                        return ResourceUtil::elideTableColumnTextInMiddle($state);
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(__('sep31_lang.label.status'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->description(__('shared_lang.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->hidden(function()  use ($table) {
                        $createdAt = $table->getColumn('created_at');
                        return $createdAt->isToggledHidden();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->description(__('shared_lang.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->hidden(function()  use ($table) {
                        $updatedAt = $table->getColumn('updated_at');
                        return $updatedAt->isToggledHidden();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->recordUrl(null)
            ->recordAction(null)
            ->filters([
                //
            ])
            ->actions([
                EditSep31TransactionResource::make()
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

    private static function getStatusFormControl(?bool $disabled = false): Select {
        return Select::make('status')
            ->label(__('sep31_lang.label.status'))
            ->columnSpanFull()
            ->disabled($disabled)
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
