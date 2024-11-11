<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEP-24 localization resources
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to support the SEP-24.
    |
    */
    'entity.name' => 'SEP-24 transaction',
    'entity.names' => 'SEP-24 transactions',
    'label.destination_asset' => 'Destination Asset',
    'label.muxed_account' => 'Muxed account',
    'label.source_asset' => 'Source asset',
    'label.status_message' => 'Status message',
    'label.status.incomplete' => 'Incomplete',
    'label.status.pending_user_transfer_start' => 'Pending user transfer start',
    'label.status.pending_user_transfer_complete' => 'Pending user transfer complete',
    'label.status.pending_external' => 'Pending external',
    'label.status.pending_anchor' => 'Pending anchor',
    'label.status.pending_stellar' => 'Pending stellar',
    'label.status.pending_trust' => 'Pending trust',
    'label.status.pending_user' => 'Pending user',
    'label.status.completed' => 'Completed',
    'label.status.refunded' => 'Refunded',
    'label.status.expired' => 'Expired',
    'label.status.no_market' => 'No marker',
    'label.status.too_small' => 'Too small',
    'label.status.too_large' => 'Too large',
    'label.status.error' => 'Error',
    'error.amount.less_than_asset_min' => 'Amount is less than asset\'s minimum limit of: :min',
    'error.amount.greater_than_asset_max' => 'Amount exceeds asset\'s maximum limit of: :max',
    'error.fee_not_supported' => 'Fee endpoint is not supported',
    'error.unsupported_operation_type' => 'Unsupported operation type: :type',
    'error.missing_operation' => 'Missing operation.',
    'error.currencies_not_supported' =>
        'This anchor doesn\'t support the given currency code: :code',
    'error.operation_not_supported_by_currency' =>
        ':operation operation id not supported for the currency code: :code',
    'error.request.source_asset_not_match_with_quote_sell_asset' =>
        'Source asset (:source_asset) does not match with the quote sell asset (:quote_sell_asset)',
    'error.request.destination_asset_not_match_with_quote_buy_asset' =>
        'Destination asset (:destination_asset) does not match with the quote buy asset (:quote_buy_asset)',
    'error.request.amount_not_match_with_quote_amount' =>
        'Amount (:amount) does not match with the quote sell amount (:quote_amount)',
    'error.account_not_found' => 'Account not found',
];
