<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anchor assets localization resources
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to support the Anchor assets.
    |
    */
    'entity.name' => 'Anchor asset',
    'entity.names' => 'Anchor assets',
    'label.code' => 'Code',
    'label.issuer' => 'Issuer',
    'label.deposit_enabled' => 'Deposit enabled',
    'label.deposit_fee_fixed' => 'Deposit fee fixed',
    'label.deposit_fee_percent' => 'Deposit fee percent',
    'label.deposit_fee_minimum' => 'Deposit fee minimum',
    'label.deposit_min_amount' => 'Deposit min amount',
    'label.deposit_max_amount' => 'Deposit max amount',
    'label.withdrawal_enabled' => 'Withdrawal enabled',
    'label.withdrawal_fee_fixed' => 'Withdrawal fee fixed',
    'label.withdrawal_fee_percent' => 'Withdrawal fee percent',
    'label.withdrawal_fee_minimum' => 'Withdrawal fee minimum',
    'label.withdrawal_min_amount' => 'Withdrawal min amount',
    'label.withdrawal_max_amount' => 'Withdrawal max amount',
    'label.significant_decimals' => 'Significant decimals',
    'label.schema' => 'Schema',
    'label.sep24_enabled' => 'SEP-24 enabled',
    'label.sep38_enabled' => 'SEP-38 enabled',
    'label.sep38_info' => 'SEP-38 info',
    'label.sep06_enabled' => 'SEP-06 enabled',
    'label.sep06_deposit_methods' => 'SEP-06 deposit methods',
    'label.sep06_deposit_method.wire' => 'WIRE',
    'label.sep06_deposit_method.cash' => 'cash',
    'label.sep06_deposit_method.mobile' => 'mobile',
    'label.sep06_withdraw_methods.wire' => 'WIRE',
    'label.sep06_withdraw_methods.cash' => 'cash',
    'label.sep06_withdraw_methods.mobile' => 'mobile',
    'label.sep06_withdraw_methods' => 'SEP-06 withdraw methods',
    'label.sep06_deposit_exchange_enabled' => 'SEP-06 deposit exchange enabled',
    'label.sep06_withdraw_exchange_enabled' => 'SEP-06 withdraw exchange enabled',
    'label.sep06_configuration' => 'SEP-06 configuration',
    'label.sep31_enabled' => 'SEP-31 enabled',
    'label.sep31_info' => 'SEP-31 info',
    'label.send_fee_fixed' => 'Send fee fixed',
    'label.send_fee_percent' => 'Send fee percent',
    'label.send_min_amount' => 'Send min amount',
    'label.send_max_amount' => 'Send max amount',
    'label.deposit_settings' => 'Deposit settings',
    'label.withdrawal_settings' => 'Withdrawal settings',
    'label.sep_configuration' => 'SEP configuration',
    'label.send_configuration' => 'Send configuration',
    'label.sep31_configuration' => 'SEP-31 configuration',
    'label.sep31_configuration.quotes_supported' => 'Quotes supported',
    'label.sep31_configuration.quotes_required' => 'Quotes required',
    'label.sep31_configuration.sep12_sender_types' => 'SEP-12 sender types',
    'label.sep31_configuration.sep12_receiver_types' => 'SEP-12 receiver types',
    'label.sep38_configuration' =>'SEP-38 configuration',
    'label.sep38_configuration.sell_delivery_methods' => 'Sell delivery methods',
    'label.sep38_configuration.buy_delivery_methods' => 'Buy delivery methods',
    'label.sep38_configuration.country_codes' => 'Country codes',
    'error.incorrect_asset_format' => 'Failed to identify the asset: :exception',
    'usdc.sep12.sender.types.sep31_sender.description' =>
        'U.S. citizens limited to sending payments of less than $10,000 in value',
    'usdc.sep12.sender.types.sep31_large_sender.description' =>
        'U.S. citizens that do not have sending limits',
    'usdc.sep12.sender.types.sep31_foreign_sender.description' =>
        'non-U.S. citizens sending payments of less than $10,000 in value',
    'usdc.sep12.receiver.types.sep31_receiver.description' =>
        'U.S. citizens receiving USD',
    'usdc.sep12.receiver.types.sep31_foreign_receiver.description' =>
        'non-U.S. citizens receiving USD',
    'jpyc.sep12.sender.types.sep31_sender.description' =>
        'U.S. citizens limited to sending payments of less than $10,000 in value',
    'jpyc.sep12.sender.types.sep31_large_sender.description' =>
        'U.S. citizens that do not have sending limits',
    'jpyc.sep12.sender.types.sep31_foreign_sender.description' =>
        'non-U.S. citizens sending payments of less than $10,000 in value',
    'jpyc.sep12.receiver.types.sep31_receiver.description' =>
        'U.S. citizens receiving USD',
    'jpyc.sep12.receiver.types.sep31_foreign_receiver.description' =>
        'non-U.S. citizens receiving USD',
    'error.invalid_asset_format' => 'The asset :asset has an invalid asset format!',
    'error.invalid_asset' => 'Invalid asset!',
    'error.request.invalid_operation_for_asset' => 'Invalid operation for asset: :asset!',
    'error.invalid_asset_in_db' => 'Invalid asset in DB!',
    'error.request.asset_code_is_required' => 'Asset code is required!',
    'error.request.invalid_asset_issuer' => 'Invalid asset issuer (must be a string and valid account id)!',
    'error.request.invalid_asset_issuer_native' => 'Invalid asset issuer :issuer for asset code \'native\'',
    'error.request.asset_not_supported' => 'Asset is not supported!',
    'error.request.invalid_destination_asset' => 'Invalid destination asset: :previous_exception',
];
