<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep06Transfer;

use App\Models\AnchorAsset;
use App\Models\Sep06Transaction;
use App\Models\Sep38Rate;
use App\Stellar\Sep12Customer\Sep12Helper;
use App\Stellar\Sep38Quote\Sep38Helper;
use App\Stellar\Shared\SepHelper;
use ArgoNavis\PhpAnchorSdk\callback\Sep06TransactionResponse;
use ArgoNavis\PhpAnchorSdk\callback\StartDepositExchangeRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartDepositRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartWithdrawExchangeRequest;
use ArgoNavis\PhpAnchorSdk\callback\StartWithdrawRequest;
use ArgoNavis\PhpAnchorSdk\callback\TransactionHistoryRequest;
use ArgoNavis\PhpAnchorSdk\exception\AccountNotFound;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\exception\QuoteNotFoundForId;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\DepositOperation;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\InstructionsField;
use ArgoNavis\PhpAnchorSdk\shared\Sep06AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep06InfoField;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\shared\WithdrawOperation;
use ArgoNavis\PhpAnchorSdk\Stellar\TrustlinesHelper;
use ArgoNavis\PhpAnchorSdk\util\MemoHelper;
use DateTime;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Memo;
use Throwable;

use function json_encode;

class Sep06Helper
{

    public const KIND_DEPOSIT = 'deposit';
    public const KIND_DEPOSIT_EXCHANGE = 'deposit-exchange';
    public const KIND_WITHDRAW = 'withdraw';
    public const KIND_WITHDRAW_EXCHANGE = 'withdraw-exchange';

    /**
     * @return array<Sep06AssetInfo> the assets having sep06 support enabled.
     */
    public static function getSupportedAssets(): array
    {
        /**
         * @var array<Sep06AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep06Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            Log::warning('There is no asset which supports SEP-06.', ['context' => 'sep06']);

            return $result;
        }
        foreach ($assets as $asset) {
            try {
                $result[] = self::sep06AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error(
                    'Invalid anchor asset.',
                    ['context' => 'sep06', 'error' => $iA->getMessage(), 'exception' => $iA, 'id' => $asset->id],
                );
            }
        }
        Log::debug(
            'The list of asset which supports SEP-06.',
            ['context' => 'sep06', 'assets' => json_encode($result)],
        );

        return $result;
    }

    /**
     * @throws InvalidAsset
     */
    private static function sep06AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset): Sep06AssetInfo
    {
        $formattedAsset = new IdentificationFormatAsset(
            $anchorAsset->schema,
            $anchorAsset->code,
            $anchorAsset->issuer,
        );

        $methodsArr = null;
        $methods = $anchorAsset->sep06_deposit_methods;
        if ($methods !== null) {
            $methodsArr = explode(',', $methods);
        }
        $depositOp = new DepositOperation(
            (bool)$anchorAsset->deposit_enabled,
            $anchorAsset->deposit_min_amount,
            $anchorAsset->deposit_max_amount,
            $anchorAsset->deposit_fee_fixed,
            $anchorAsset->deposit_fee_percent,
            $anchorAsset->deposit_fee_minimum,
            array_map('trim', $methodsArr),
        );

        $methodsArr = null;
        $methods = $anchorAsset->sep06_withdraw_methods;
        if ($methods !== null) {
            $methodsArr = explode(',', $methods);
        }
        $withdrawOp = new WithdrawOperation(
            (bool)$anchorAsset->withdrawal_enabled,
            $anchorAsset->withdrawal_min_amount,
            $anchorAsset->withdrawal_max_amount,
            $anchorAsset->withdrawal_fee_fixed,
            $anchorAsset->withdrawal_fee_percent,
            $anchorAsset->withdrawal_fee_minimum,
            array_map('trim', $methodsArr),
        );

        return new Sep06AssetInfo(
            asset:$formattedAsset,
            depositOperation: $depositOp,
            withdrawOperation: $withdrawOp,
            depositExchangeEnabled: (bool)$anchorAsset->sep06_deposit_exchange_enabled,
            withdrawExchangeEnabled: (bool)$anchorAsset->sep06_withdraw_exchange_enabled,
        );
    }

    /**
     * Creates and saves a new deposit transaction from the given request data.
     * @param StartDepositRequest $request the request data.
     * @return Sep06Transaction then new created deposit transaction.
     * @throws AnchorFailure
     */
    public static function newDepositTransaction(StartDepositRequest $request) : Sep06Transaction
    {
        Log::debug(
            'New deposit transaction.',
            ['context' => 'sep06', 'operation' => 'new_deposit', 'deposit_request' => json_encode($request)],
        );
        $destinationAssetCode = $request->depositAsset->asset->getCode();
        $destinationAssetIssuer = $request->depositAsset->asset->getIssuer();
        $claimableBalanceSupported = $request->claimableBalanceSupported === null ? false : $request->claimableBalanceSupported;

        $sep06Transaction = new Sep06Transaction;
        $sep06Transaction->status = self::getNewTransactionStatus($request->sep10Account, $request->sep10AccountMemo);
        if ($sep06Transaction->status === Sep06TransactionStatus::PENDING_USER_TRANSFER_START) {
            if (!$claimableBalanceSupported && $destinationAssetIssuer !== null) {
                $needsTrustline = self::needsDepositTrustline(
                    receivingAccountId: $request->account,
                    assetCode: $destinationAssetCode,
                    assetIssuer: $destinationAssetIssuer,
                );
                if ($needsTrustline) {
                    $sep06Transaction->status = Sep06TransactionStatus::PENDING_TRUST;
                }
            }
        }
        $sep06Transaction->kind = self::KIND_DEPOSIT;
        $sep06Transaction->type = $request->type;
        $sep06Transaction->request_asset_code = $destinationAssetCode;
        $sep06Transaction->request_asset_issuer = $destinationAssetIssuer;
        $sep06Transaction->amount_expected = $request->amount;
        $start = new DateTime('now');
        $sep06Transaction->tx_started_at = $start->format(DateTimeInterface::ATOM);
        $sep06Transaction->sep10_account = $request->sep10Account;
        $sep06Transaction->sep10_account_memo = $request->sep10AccountMemo;
        $sep06Transaction->to_account = $request->account;
        $sep06Transaction->client_domain = $request->clientDomain;
        if ($request->memo !== null) {
            $memoFields = self::memoFieldsFromMemo($request->memo);
            $sep06Transaction->memo = $memoFields['memo_value'];
            $sep06Transaction->memo_type = $memoFields['memo_type'];
        }
        $sep06Transaction->claimable_balance_supported = $claimableBalanceSupported;

        // add your own business logic here
        $instructions = [
            (new InstructionsField(
                name: 'bank_number',
                value: '121122676',
                description: 'Fake bank number'
            ))->toJson(),
            (new InstructionsField(
                name: 'bank_account_number',
                value: '13719713158835300',
                description: 'Fake bank account number'
            ))->toJson(),
        ];
        $sep06Transaction->instructions = json_encode($instructions);

        // save
        $sep06Transaction->save();
        $sep06Transaction->refresh();
        Log::debug(
            'The transaction has been saved successfully.',
            ['context' => 'sep06', 'operation' => 'new_deposit', 'transaction' => json_encode($sep06Transaction)],
        );
        return $sep06Transaction;
    }

    /**
     * Creates and stores a new deposit exchange transaction from the given request data.
     * @param StartDepositExchangeRequest $request the request data to create the transaction from.
     * @return Sep06Transaction the new created deposit exchange transaction.
     * @throws AnchorFailure
     */
    public static function newDepositExchangeTransaction(StartDepositExchangeRequest $request) : Sep06Transaction
    {
        Log::debug(
            'New deposit exchange transaction.',
            ['context' => 'sep06', 'operation' => 'new_deposit_exchange', 'deposit_request' => json_encode($request)],
        );

        $destinationAssetCode = $request->destinationAsset->asset->getCode();
        $destinationAssetIssuer = $request->destinationAsset->asset->getIssuer();
        $claimableBalanceSupported = $request->claimableBalanceSupported === null ? false : $request->claimableBalanceSupported;

        $sep06Transaction = new Sep06Transaction;
        $sep06Transaction->status = self::getNewTransactionStatus($request->sep10Account, $request->sep10AccountMemo);
        if ($sep06Transaction->status === Sep06TransactionStatus::PENDING_USER_TRANSFER_START) {
            if (!$claimableBalanceSupported && $destinationAssetIssuer !== null) {
                $needsTrustline = self::needsDepositTrustline(
                    receivingAccountId: $request->account,
                    assetCode: $destinationAssetCode,
                    assetIssuer: $destinationAssetIssuer,
                );
                if ($needsTrustline) {
                    $sep06Transaction->status = Sep06TransactionStatus::PENDING_TRUST;
                }
            }
        }
        $sep06Transaction->kind = self::KIND_DEPOSIT_EXCHANGE;
        $sep06Transaction->type = $request->type;
        $sep06Transaction->request_asset_code = $destinationAssetCode;
        $sep06Transaction->request_asset_issuer = $destinationAssetIssuer;
        $sep06Transaction->amount_in = 0.0;
        $sep06Transaction->amount_in_asset = $request->sourceAsset->getStringRepresentation();
        $sep06Transaction->amount_out_asset = $request->destinationAsset->asset->getStringRepresentation();

        // add you business logic here
        // calculate amount out here. if quoteId != null take it from the quote,
        // otherwise calculate with your business logic.
        // $sep06Transaction->amount_out = ...;
        // $sep06Transaction->feeDetails = ...;
        // others ... e.g. :
        if ($request->quoteId !== null) {
            try {
                $quote = Sep38Helper::getQuoteById($request->quoteId, $request->sep10Account, $request->sep10AccountMemo);
                $sep06Transaction->fee_details = json_encode($quote->fee->toJson());
                $sep06Transaction->amount_out = $quote->buyAmount;
            } catch (QuoteNotFoundForId $qe) {
                Log::error(
                    'Quote not found.',
                    ['context' => 'sep06', 'operation' => 'new_deposit_exchange',
                        'quote_id' => $request->quoteId, 'error' => $qe->getMessage(), 'exception' => $qe,
                    ],
                );

                throw new AnchorFailure(
                    message: $qe->getMessage(),
                    code: $qe->getCode(),
                    messageKey: $qe->getMessageKey(),
                    messageParams: $qe->getMessageParams(),
                );
            } catch (Throwable $e) {
                Log::error(
                    'Quote not found.',
                    ['context' => 'sep06', 'operation' => 'new_deposit_exchange',
                        'quote_id' => $request->quoteId, 'error' => $e->getMessage(), 'exception' => $e,
                    ],
                );

                throw new AnchorFailure(
                    message: $e->getMessage(),
                    code: $e->getCode(),
                    messageKey: 'shared_lang.error.anchor_failure',
                );
            }
        } else {
            $feeInfo = new TransactionFeeInfo(
                total: '0.1',
                asset: $request->destinationAsset->asset,
                details: [new TransactionFeeInfoDetail(
                    name: 'Service fee',
                    amount: '0.1'
                )]
            );
            $sep06Transaction->fee_details = json_encode($feeInfo->toJson());
        }
        $instructions = [
            (new InstructionsField(
                name: 'bank_number',
                value: '121122676',
                description: 'Fake bank number'
            ))->toJson(),
            (new InstructionsField(
                name: 'bank_account_number',
                value: '13719713158835300',
                description: 'Fake bank account number'
            ))->toJson(),
        ];
        $sep06Transaction->instructions = json_encode($instructions);

        $sep06Transaction->quote_id = $request->quoteId;
        $sep06Transaction->amount_expected = $request->amount;
        $start = new DateTime('now');
        $sep06Transaction->tx_started_at = $start->format(DateTimeInterface::ATOM);
        $sep06Transaction->sep10_account = $request->sep10Account;
        $sep06Transaction->sep10_account_memo = $request->sep10AccountMemo;
        $sep06Transaction->to_account = $request->account;
        $sep06Transaction->client_domain = $request->clientDomain;
        if ($request->memo !== null) {
            $memoFields = self::memoFieldsFromMemo($request->memo);
            $sep06Transaction->memo = $memoFields['memo_value'];
            $sep06Transaction->memo_type = $memoFields['memo_type'];
        }
        $sep06Transaction->claimable_balance_supported = $claimableBalanceSupported;
        $sep06Transaction->save();
        $sep06Transaction->refresh();
        Log::debug(
            'The transaction has been saved successfully.',
            ['context' => 'sep06', 'operation' => 'new_deposit_exchange',
                'transaction' => json_encode($sep06Transaction),
            ],
        );

        return $sep06Transaction;
    }

    /**
     * Creates and stores a new withdrawal transaction from the given request data.
     * @param StartWithdrawRequest $request the request data to create the transaction from.
     * @return Sep06Transaction the new created withdrawal transaction.
     * @throws AnchorFailure
     */
    public static function newWithdrawTransaction(StartWithdrawRequest $request) : Sep06Transaction
    {
        Log::debug(
            'New withdraw transaction.',
            ['context' => 'sep06', 'operation' => 'new_withdraw', 'withdraw_request' => json_encode($request)],
        );
        $sep06Transaction = new Sep06Transaction;
        $sep06Transaction->status = self::getNewTransactionStatus($request->sep10Account, $request->sep10AccountMemo);
        $sep06Transaction->kind = self::KIND_WITHDRAW;
        $sep06Transaction->type = $request->type;
        $sep06Transaction->request_asset_code = $request->asset->asset->getCode();
        $sep06Transaction->request_asset_issuer = $request->asset->asset->getIssuer();
        $sep06Transaction->amount_in = $request->amount;
        $sep06Transaction->amount_in_asset = $request->asset->asset->getStringRepresentation();
        $sep06Transaction->amount_expected = $request->amount;
        $start = new DateTime('now');
        $sep06Transaction->tx_started_at = $start->format(DateTimeInterface::ATOM);
        $sep06Transaction->sep10_account = $request->sep10Account;
        $sep06Transaction->sep10_account_memo = $request->sep10AccountMemo;
        $account = $request->account ?? $request->sep10Account;
        $sep06Transaction->from_account = $account;
        $sep06Transaction->client_domain = $request->clientDomain;
        if ($request->refundMemo !== null) {
            $memoFields = self::memoFieldsFromMemo($request->refundMemo);
            $sep06Transaction->refund_memo = $memoFields['memo_value'];
            $sep06Transaction->refund_memo_type = $memoFields['memo_type'];
        }

        // add your business logic here. e.g.
        if ($sep06Transaction->request_asset_code === config('stellar.assets.usdc_asset_code')) {
            $sep06Transaction->withdraw_anchor_account = config('stellar.assets.usdc_asset_distribution_account_id');
            $sep06Transaction->amount_out_asset = 'iso4217:USD';
        } elseif ($sep06Transaction->request_asset_code === config('stellar.assets.jpyc_asset_code')) {
            $sep06Transaction->withdraw_anchor_account = config('stellar.assets.jpyc_asset_distribution_account_id');
            $sep06Transaction->amount_out_asset = 'iso4217:JPYC';
        }
        $sep06Transaction->memo = strval(rand(5000000, 150000000));
        $sep06Transaction->memo_type = 'id';

        $sep06Transaction->save();
        $sep06Transaction->refresh();
        Log::debug(
            'The transaction has been saved successfully.',
            ['context' => 'sep06', 'operation' => 'new_withdraw', 'transaction' => json_encode($sep06Transaction)],
        );

        return $sep06Transaction;
    }

    private static function getKYCStatus(string $accountId, ?string $memo) : ?string
    {
        Log::debug('Loading KYC status.', ['context' => 'sep06', 'account_id' => $accountId, 'memo' => $memo]);

        $sep12Customer = Sep12Helper::getSep12CustomerByAccountId(
            accountId: $accountId,
            memo: $memo
        );
        Log::debug(
            'SEP-12 customer loaded.',
            ['context' => 'sep06', 'customer' => json_encode($sep12Customer)],
        );
        if ($sep12Customer == null) {
            Log::error(
                'SEP-12 customer not found.',
                ['context' => 'sep06', 'account_id' => $accountId, 'memo' => $memo],
            );
        }

        return $sep12Customer?->status;
    }

    /**
     * @throws AnchorFailure
     */
    private static function getNewTransactionStatus(
        string $sep10AccountId,
        ?string $sep10AccountMemo,
    ) : ?string {
        $kycStatus = self::getKYCStatus($sep10AccountId, $sep10AccountMemo);

        if ($kycStatus === CustomerStatus::ACCEPTED) {
            return Sep06TransactionStatus::PENDING_USER_TRANSFER_START;
        } elseif ($kycStatus === CustomerStatus::REJECTED) {
            throw new AnchorFailure(
                message: 'Customer KYC has status rejected',
                messageKey: 'sep31_lang.error.new_transaction.kyc_status_rejected',
            );
        } elseif ($kycStatus === null || $kycStatus === CustomerStatus::NEEDS_INFO) {
            return Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE;
        }
        return Sep06TransactionStatus::INCOMPLETE;
    }

    private static function needsDepositTrustline(
        string $receivingAccountId,
        string $assetCode,
        string $assetIssuer,
    ) : bool {
        $trustlineNeeded = true;
        try {
            TrustlinesHelper::setLogger(Log::getLogger());
            $horizonUrl = config('stellar.app.horizon_url');

            $trustlineNeeded = !TrustlinesHelper::checkIfAccountTrustsAsset(
                horizonUrl: $horizonUrl,
                accountId: $receivingAccountId,
                assetCode: $assetCode,
                assetIssuer: $assetIssuer,
            );
        } catch (Exception $e) {
            if ($e instanceof AccountNotFound) {
                Log::error(
                    'Failed to check if account trust asset, account not found.',
                    ['context' => 'sep06', 'error' => $e->getMessage(), 'exception' => $e],
                );
                $trustlineNeeded = false;
            }
        }
        Log::debug(
            'Verifying if trustline needed for deposit.',
            ['context' => 'sep06', 'horizon_url' => $horizonUrl, 'receiving_account_id' => $receivingAccountId,
                'asset_code' => $assetCode, 'asset_issuer' => $assetIssuer, 'trustline_needed' => $trustlineNeeded],
        );

        return $trustlineNeeded;
    }

    /**
     * Creates and stores a new withdrawal exchange transaction from the given request data.
     * @param StartWithdrawExchangeRequest $request the request data to create the transaction from.
     * @return Sep06Transaction the new created withdrawal exchange transaction.
     * @throws AnchorFailure
     */
    public static function newWithdrawExchangeTransaction(StartWithdrawExchangeRequest $request) : Sep06Transaction
    {
        Log::debug(
            'New withdraw exchange transaction.',
            ['context' => 'sep06', 'operation' => 'new_withdraw_exchange',
                'withdraw_exchange_request' => json_encode($request)],
        );
        $sep06Transaction = new Sep06Transaction;
        $sep06Transaction->status = self::getNewTransactionStatus($request->sep10Account, $request->sep10AccountMemo);
        $sep06Transaction->kind = 'withdraw-exchange';
        $sep06Transaction->type = $request->type;
        $sep06Transaction->request_asset_code = $request->sourceAsset->asset->getCode();
        $sep06Transaction->request_asset_issuer = $request->sourceAsset->asset->getIssuer();
        $sep06Transaction->amount_in = $request->amount;
        $sep06Transaction->amount_in_asset = $request->sourceAsset->asset->getStringRepresentation();
        $sep06Transaction->amount_out_asset = $request->destinationAsset->getStringRepresentation();

        // calculate amount out here. if quoteId != null take it from the quote,
        // otherwise calculate with your business logic.
        // $sep06Transaction->amount_out = ...;
        // $sep06Transaction->feeDetails = ...;
        // others ..
        if ($request->quoteId !== null) {
            try {
                $quote = Sep38Helper::getQuoteById($request->quoteId, $request->sep10Account, $request->sep10AccountMemo);
                $sep06Transaction->fee_details = json_encode($quote->fee->toJson());
                $sep06Transaction->amount_out = $quote->buyAmount;
            } catch (QuoteNotFoundForId $qe) {
                Log::error(
                    'Quote not found.',
                    ['context' => 'sep06', 'operation' => 'new_deposit_exchange',
                        'quote_id' => $request->quoteId, 'error' => $qe->getMessage(), 'exception' => $qe,
                    ],
                );

                throw new AnchorFailure(
                    message: $qe->getMessage(),
                    code: $qe->getCode(),
                    messageKey: $qe->getMessageKey(),
                    messageParams: $qe->getMessageParams(),
                );
            } catch (Throwable $e) {
                Log::debug(
                    'Quote not found.',
                    ['context' => 'sep06', 'operation' => 'new_withdraw_exchange',
                        'quote_id' => $request->quoteId, 'error' => $e->getMessage(), 'exception' => $e,
                    ],
                );

                throw new AnchorFailure(
                    message: $e->getMessage(),
                    code: $e->getCode(),
                    messageKey: 'shared_lang.error.anchor_failure',
                );
            }
        } else {
            // based on exchange rate
            $exchangeRate = Sep38Rate::where('sell_asset', '=', $sep06Transaction->amount_in_asset)
                ->where('buy_asset', '=', $sep06Transaction->amount_out_asset)->first();
            Log::debug(
                'Exchange rate found.',
                ['context' => 'sep06', 'operation' => 'new_withdraw_exchange',
                    'exchange_rate' => json_encode($exchangeRate),
                ],
            );

            if ($exchangeRate === null) {
                throw new AnchorFailure(
                    message: 'no exchange rate found for sell asset ' .
                        $sep06Transaction->amount_in_asset . ' and buy asset '. $sep06Transaction->amount_in_asset,
                    messageKey: 'sep31_lang.error.withdraw_exchange_no_rate',
                    messageParams: [
                        'sell_asset' => $sep06Transaction->amount_in_asset,
                        'buy_asset' => $sep06Transaction->amount_out_asset,
                    ],
                );
            }
            $feePercent = $exchangeRate->fee_percent;
            $fee = $sep06Transaction->amount_in * ($feePercent / 100);
            $amountInMinusFee = $sep06Transaction->amount_in - $fee;
            $sep06Transaction->amount_out = $amountInMinusFee * $exchangeRate->rate;

            $feeInfo = new TransactionFeeInfo(
                total: strval($fee),
                asset: $request->sourceAsset->asset,
                details: [new TransactionFeeInfoDetail(
                    name: 'Service fee',
                    amount: strval($fee)
                )]
            );
            $sep06Transaction->fee_details = json_encode($feeInfo->toJson());
        }
        if ($sep06Transaction->request_asset_code === config('stellar.assets.usdc_asset_code')) {
            $sep06Transaction->withdraw_anchor_account = config('stellar.assets.usdc_asset_distribution_account_id');
        } elseif ($sep06Transaction->request_asset_code === config('stellar.assets.jpyc_asset_code')) {
            $sep06Transaction->withdraw_anchor_account = config('stellar.assets.jpyc_asset_distribution_account_id');
        }
        $sep06Transaction->memo = strval(rand(5000000, 150000000));
        $sep06Transaction->memo_type = 'id';

        $sep06Transaction->amount_expected = $request->amount;

        $start = new DateTime('now');
        $sep06Transaction->tx_started_at = $start->format(DateTimeInterface::ATOM);
        $sep06Transaction->sep10_account = $request->sep10Account;
        $sep06Transaction->sep10_account_memo = $request->sep10AccountMemo;
        $account = $request->account ?? $request->sep10Account;
        $sep06Transaction->from_account = $account;
        $sep06Transaction->client_domain = $request->clientDomain;
        if ($request->refundMemo !== null) {
            $memoFields = self::memoFieldsFromMemo($request->refundMemo);
            $sep06Transaction->refund_memo = $memoFields['memo_value'];
            $sep06Transaction->refund_memo_type = $memoFields['memo_type'];
        }
        $sep06Transaction->quote_id = $request->quoteId;

        $sep06Transaction->save();
        $sep06Transaction->refresh();

        Log::debug(
            'The transaction has been saved successfully.',
            ['context' => 'sep06', 'operation' => 'new_withdraw_exchange',
                'transaction' => json_encode($sep06Transaction),
            ],
        );

        return $sep06Transaction;
    }

    public static function getTransaction(
        string $accountId,
        ?string $memo = null,
        ?string $id = null,
        ?string $stellarTxId = null,
        ?string $externalTxId = null
    ) : ?Sep06TransactionResponse {
        Log::debug(
            'Loading transaction.',
            ['context' => 'sep06', 'account_id' => $accountId, 'memo' => $memo,
                'id' => $id, 'stellar_tx_id' => $stellarTxId, 'external_tx_id' => $externalTxId,
            ],
        );

        $query = ['sep10_account' => $accountId];
        if ($id !== null) {
            $query['id'] = $id;
        } elseif ($stellarTxId !== null) {
            $query['stellar_transaction_id'] = $stellarTxId;
        } elseif ($externalTxId !== null) {
            $query['external_transaction_id'] = $externalTxId;
        }

        if ($memo !== null) {
            $query['sep10_account_memo'] = $memo;
        }
        $tx = Sep06Transaction::where($query)->first();
        if ($tx !== null) {
            return self::sep06TransactionResponseFromTx($tx);
        }
        Log::error(
            'Transaction not found.',
            ['context' => 'sep06', 'account_id' => $accountId, 'memo' => $memo,
                'id' => $id, 'stellar_tx_id' => $stellarTxId, 'external_tx_id' => $externalTxId],
        );

        return null;
    }

    /**
     * @throws AnchorFailure
     */
    public static function getTransactionHistory(
        TransactionHistoryRequest $request,
        string $accountId,
        ?string $memo = null,
    ) : ?array {
        Log::debug(
            'Loading transaction history.',
            ['context' => 'sep06', 'account_id' => $accountId, 'memo' => $memo, 'request' => json_encode($request)],
        );

        $baseQuery = [
            'sep10_account' => $accountId,
            'request_asset_code' => $request->assetCode,
        ];

        if ($memo !== null) {
            $baseQuery['sep10_account_memo'] = $memo;
        }

        if ($request->noOlderThan != null) {
            $baseQuery['sep10_account_memo'] = $memo;
        }

        // TODO paging_id

        $txsQueryBuilder = Sep06Transaction::where($baseQuery);
        if ($request->noOlderThan != null) {
            $dateStr  = $request->noOlderThan->format(DateTimeInterface::ATOM);
            $txsQueryBuilder = Sep06Transaction::where(function ($query) use ($dateStr) {
                $query->where('tx_started_at', '>=', $dateStr);
            })->where($baseQuery);
        }

        $txsQueryBuilder = $txsQueryBuilder->orderBy('tx_started_at');

        if ($request->limit != null) {
            $txsQueryBuilder = $txsQueryBuilder->limit($request->limit);
        }

        $txs = $txsQueryBuilder->get();
        if ($txs !== null && count($txs) > 0) {
            $sep06Transactions = [];
            foreach ($txs as $tx) {
                $sep06Transactions[] = self::sep06TransactionResponseFromTx($tx);
            }

            return $sep06Transactions;
        }
        Log::debug(
            'Transaction history is empty.',
            ['context' => 'sep06', 'account_id' => $accountId, 'memo' => $memo],
        );

        return null;
    }

    /**
     * @throws AnchorFailure
     */
    private static function sep06TransactionResponseFromTx(Sep06Transaction $tx) : Sep06TransactionResponse
    {

        try {
            $amountInAsset = $tx->amount_in_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_in_asset) : null;
            $amountOutAsset = $tx->amount_out_asset !== null ?
                IdentificationFormatAsset::fromString($tx->amount_out_asset) : null;
        } catch (InvalidAsset $ia) {
            Log::debug(
                'Converting db. transaction to transaction response failed, invalid asset in DB.',
                ['context' => 'sep06', 'error' => $ia->getMessage(), 'exception' => $ia, 'http_status_code' => 500],
            );

            throw new AnchorFailure(
                message: 'Invalid asset in DB',
                code: 500,
                messageKey: 'shared_lang.error.invalid_asset_in_db',
            );
        }

        $response =  new Sep06TransactionResponse(
            id: $tx->id,
            kind: $tx->kind,
            status: $tx->status,
            statusEta: $tx->status_eta,
            moreInfoUrl: $tx->more_info_url,
            amountIn: $tx->amount_in === null ? null : strval($tx->amount_in),
            amountInAsset: $amountInAsset,
            amountOut: $tx->amount_out === null ? null : strval($tx->amount_out),
            amountOutAsset: $amountOutAsset,
            quoteId: $tx->quote_id,
            from: $tx->from_account,
            to: $tx->to_account,
            startedAt: DateTime::createFromFormat(DATE_ATOM, $tx->tx_started_at),
            completedAt: $tx->tx_completed_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_completed_at),
            updatedAt: $tx->tx_updated_at === null ? null : DateTime::createFromFormat(DATE_ATOM, $tx->tx_updated_at),
            stellarTransactionId: $tx->stellar_transaction_id,
            externalTransactionId: $tx->external_transaction_id,
            message: $tx->message,
            requiredInfoMessage: $tx->required_info_message,
            requiredInfoUpdates: $tx->required_info_updates,
            claimableBalanceId: $tx->claimable_balance_id,
        );

        if ($tx->required_info_updates != null) {
            $response->requiredInfoUpdates = self::parseRequiredInfoUpdates($tx->required_info_updates);
        }

        if (str_contains($tx->kind, 'deposit')) {
            $response->depositMemo = $tx->memo;
            $response->depositMemoType = $tx->memo_type;
        } else {
            $response->withdrawMemo = $tx->memo;
            $response->withdrawMemoType = $tx->memo_type;
            $response->withdrawAnchorAccount = $tx->withdraw_anchor_account;
        }

        // instructions
        if ($tx->instructions != null) {
            $response->instructions = self::parseInstructions($tx->instructions);
        }

        // feeDetails
        if ($tx->fee_details != null) {
            $response->feeDetails = SepHelper::parseFeeDetails($tx->fee_details);
        }

        // refunds
        if ($tx->refunds != null) {
            $response->refunds = SepHelper::parseRefunds($tx->refunds);
        }
        Log::debug(
            'Converting db. transaction to transaction response.',
            ['context' => 'sep06', 'db_transaction' => json_encode($tx),
                'sep_06_transaction_response' => json_encode($response),
            ],
        );

        return $response;
    }

    /**
     * Parses the instructions into an array of InstructionsField from a given json string.
     * Example json string:
     * "[
     *      "organization.bank_number": {
     *          "value": "121122676",
     *          "description": "Fake bank number"
     *       },
     *      "organization.bank_account_number": {
     *          "value": "13719713158835300",
     *          "description": "Fake bank account number"
     *      }
     * ]"
     * see also: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#response
     *
     * @param string $instructionsJson the json string to parse from
     * @return array<InstructionsField>|null the result if it could be parsed.
     */
    private static function parseInstructions(string $instructionsJson) : ?array
    {
        Log::debug(
            'Parsing instructions JSON string.',
            ['context' => 'sep06', 'instructions_json' => $instructionsJson],
        );
        $instructions = json_decode($instructionsJson, true);
        if (is_array($instructions)) {
            /**
             * @var array<InstructionsField>$responseInstructions
             */
            $responseInstructions = [];
            foreach ($instructions as $value) {
                if (isset($value['name']) && isset($value['value']) && isset($value['description'])) {
                    $responseInstructions[] = new InstructionsField(
                        name: $value['name'],
                        value:$value['value'],
                        description: $value['description'],
                    );
                } else {
                    Log::warning(
                        'Incorrect instruction JSON object.',
                        ['context' => 'sep06', 'instructions' => json_encode($value)]
                    );
                }
            }
            return $responseInstructions;
        } else {
            Log::warning('Incorrect instructions JSON.', ['context' => 'sep06']);
        }
        return null;
    }

    /**
     * Parses the required info updates into an array of Sep06InfoField from a given json string.
     *
     * Example json string:
     * [
     *      "amount" : {
     *          "description": "amount in USD that you plan to deposit"
     *      },
     *      "country_code": {
     *          "description": "The ISO 3166-1 alpha-3 code of the user's current address",
     *          "choices": ["USA", "PRI"],
     *          "optional" true
     *      }
     * ]
     *
     *
     * @param string $requiredInfoUpdatesJson the json string to parse from
     * @return Sep06InfoField[]|null the result if it could be parsed.
     */
    public static function parseRequiredInfoUpdates(string $requiredInfoUpdatesJson) : ?array
    {
        Log::debug(
            'Parsing required info updates JSON string.',
            ['context' => 'sep06', 'required_info_updates_json' => $requiredInfoUpdatesJson],
        );
        $infoUpdates = json_decode($requiredInfoUpdatesJson, true);
        if ($infoUpdates !== null) {
            /**
             * @var array<Sep06InfoField>$responseInfoUpdates
             */
            $responseInfoUpdates = [];
            foreach ($infoUpdates as $key => $value) {
                if (isset($value['description'])) {
                    $infoField = new Sep06InfoField(
                        fieldName: $key,
                        description:$value['description'],
                    );
                    if (isset($value['choices']) && is_array($value['choices'])) {
                        /**
                         * @var array<string> $choices
                         */
                        $choices = [];
                        foreach ($value['choices'] as $choice) {
                            if (is_string($choice)) {
                                $choices[] = $choice;
                            }
                        }
                        $infoField->choices = $choices;
                    }
                    if (isset($value['optional'])) {
                        $infoField->optional = (bool)$value['optional'];
                    }
                    $responseInfoUpdates[] = $infoField;
                } else {
                    Log::warning(
                        'Incorrect required info update JSON object.',
                        ['context' => 'sep06', 'required_info_update_json' => json_encode($value)],
                    );
                }
            }
            return $responseInfoUpdates;
        }
        Log::warning('Incorrect or empty required info update JSON.', ['context' => 'sep06']);

        return null;
    }

    /**
     * Extracts type and value as strings from a memo to be saved in the db
     * @param Memo $memo the memo to extract the values from
     * @return array<string,?string> keys: memo_type and memo_value
     */
    private static function memoFieldsFromMemo(Memo $memo) : array
    {
        $memoType = MemoHelper::memoTypeAsString($memo->getType());
        $memoValue = null;
        if ($memoType === 'hash' || $memoType === 'return') {
            $memoValue = base64_encode($memo->getValue());
        } elseif ($memo->getValue() !== null) {
            $memoValue = strval($memo->getValue());
        }

        return [
            'memo_type' => MemoHelper::memoTypeAsString($memo->getType()),
            'memo_value' =>$memoValue
        ];
    }
}
