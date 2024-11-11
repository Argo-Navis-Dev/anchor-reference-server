<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEP-31 localization resources
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to support the SEP-31.
    |
    */
    'entity.name' => 'SEP-31 transaction',
    'entity.names' => 'SEP-31 transactions',
    'label.stellar_transaction_id' => 'Stellar transaction id',
    'label.external_transaction_id' => 'External transaction id',
    'label.status' => 'Status',
    'label.status_eta' => 'Status eta',
    'label.amount_expected' => 'Amount expected',
    'label.amount_in' => 'Amount in',
    'label.amount_in_asset' => 'Amount in asset',
    'label.amount_out' => 'Amount out',
    'label.amount_out_asset' => 'Amount out asset',
    'label.amount_fee' => 'Amount fee',
    'label.amount_fee_asset' => 'Amount fee asset',
    'label.stellar_account_id' => 'Stellar account id',
    'label.stellar_memo' => 'Stellar memo',
    'label.stellar_memo_type' => 'Stellar memo type',
    'label.client_domain' => 'Client domain',
    'label.tx_started_at' => 'Transaction started at',
    'label.tx_completed_at' => 'Transaction completed at',
    'label.tx_updated_at' => 'Transaction updated at',
    'label.transfer_received_at' => 'Transfer received at',
    'label.stellar_transactions' => 'Stellar transactions',
    'label.sep10_account' => 'SEP-10 account',
    'label.sep10_account_memo' => 'SEP-10 account memo',
    'label.quote_id' => 'Quote id',
    'label.sender_id' => 'Sender id',
    'label.receiver_id' => 'Receiver id',
    'label.callback_url' => 'Callback URL',
    'label.message' => 'Message',
    'label.refunds' => 'Refunds',
    'label.refund_memo' => 'Refund memo',
    'label.refund_memo_type' => 'Refund memo type',
    'label.fee_details' => 'Fee details',
    'label.required_info_message' => 'Required info message',
    'label.required_customer_info_updates' => 'Required customer info updates',
    'label.status.pending_sender' => 'Pending sender',
    'label.status.pending_stellar' => 'Pending stellar',
    'label.status.pending_customer_info_update' => 'Pending customer info update',
    'label.status.pending_transaction_info_update' => 'Pending transaction info update',
    'label.status.pending_receiver' => 'Pending receiver',
    'label.status.pending_external' => 'Pending external',
    'label.status.completed' => 'Completed',
    'label.status.refunded' => 'Refunded',
    'label.status.expired' => 'Expired',
    'label.status.error' => 'Error',
    'error.request.amount_required' => ' Amount is required!',
    'error.request.invalid_asset_code' => ' Invalid asset code (must be a string)!',
    'error.request.invalid_amount' => ' Invalid amount: :amount for asset :asset!',
    'error.request.destination_asset_must_be_a_string' => ' Invalid destination asset (must be a string)!',
    'error.request.field_greater_than_zero' => ' :field must be greater than zero!',
    'error.request.destination_asset_not_supported' => ' Destination asset not supported. Can not find price!',
    'error.request.quote.sell_asset_source_asset_not_match' =>
        ' Quote sell asset does not match source asset: :asset!',
    'error.request.quote.buy_asset_destination_asset_not_match' =>
        ' Quote buy asset does not match destination asset: :code!',
    'error.request.quote.amount_request_amount_not_match' => ' Quote amount does not match request amount!',
    'error.request.quote.not_supported' => ' Quote not supported by the Anchor but quote_id field passed!',
    'error.request.sender_id_required' => ' The field sender_id is required!',
    'error.request.receiver_id_required' => ' The field receiver_id is required!',
    'error.asset.sep_31_info_missing' => ' SEP-31 info missing for asset: :asset!',
    'error.transaction_not_created' => ' Error while creating the transaction.',
];
