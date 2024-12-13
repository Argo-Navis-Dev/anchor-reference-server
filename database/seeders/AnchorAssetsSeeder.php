<?php

namespace Database\Seeders;

use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep12Type;
use ArgoNavis\PhpAnchorSdk\shared\Sep31AssetInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use function json_encode;

class AnchorAssetsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sep31Info = $this->composeSep31Info();
        $usdcAssetCode = config('stellar.assets.usdc_asset_code');
        $usdcAssetIssuerId = config('stellar.assets.usdc_asset_issuer_id');

        DB::table('anchor_assets')->insert([
            'schema' => 'stellar',
            'code' => $usdcAssetCode,
            'issuer' => $usdcAssetIssuerId,
            'significant_decimals' => 2,
            'deposit_enabled' => true,
            'deposit_min_amount' => 1.0,
            'deposit_max_amount' => 1000.0,
            'withdrawal_enabled' => true,
            'withdrawal_min_amount' => 1.0,
            'withdrawal_max_amount' => 1000.0,
            'sep38_enabled' => true,
            'sep06_enabled' => true,
            'sep06_deposit_exchange_enabled' => true,
            'sep06_withdraw_exchange_enabled' => true,
            'sep06_deposit_methods' => 'bank_account',
            'sep06_withdraw_methods' => 'bank_account',
            'sep31_enabled' => true,
            'sep31_info' => json_encode($sep31Info[$usdcAssetCode]->toJson()),
            'send_min_amount' => 1.0,
            'send_max_amount' => 1000.0,
            'created_at' => now(),
        ]);

        $jpycAssetCode = config('stellar.assets.jpyc_asset_code');
        $jpycAssetIssuerId = config('stellar.assets.jpyc_asset_issuer_id');

        DB::table('anchor_assets')->insert([
            'schema' => 'stellar',
            'code' => $jpycAssetCode,
            'issuer' => $jpycAssetIssuerId,
            'significant_decimals' => 2,
            'deposit_enabled' => true,
            'deposit_min_amount' => 1.0,
            'deposit_max_amount' => 1000000.0,
            'withdrawal_enabled' => true,
            'withdrawal_min_amount' => 1.0,
            'withdrawal_max_amount' => 1000000.0,
            'sep38_enabled' => true,
            'sep06_enabled' => true,
            'sep06_deposit_exchange_enabled' => true,
            'sep06_withdraw_exchange_enabled' => true,
            'sep06_deposit_methods' => 'bank_account',
            'sep06_withdraw_methods' => 'bank_account',
            'sep31_enabled' => true,
            'sep31_info' => json_encode($sep31Info[$jpycAssetCode]->toJson()),
            'send_min_amount' => 1.0,
            'send_max_amount' => 1000000.0,
            'created_at' => now(),
        ]);

        DB::table('anchor_assets')->insert([
            'schema' => 'iso4217',
            'code' => 'USD',
            'significant_decimals' => 2,
            'deposit_enabled' => true,
            'deposit_min_amount' => 1,
            'deposit_max_amount' => 1000000,
            'withdrawal_enabled' => true,
            'withdrawal_min_amount' => 1,
            'withdrawal_max_amount' => 1000000,
            'sep38_enabled' => true,
            'sep38_info' =>
                '{
                      "country_codes": [
                        "USA"
                      ],
                      "decimals": 4,
                      "sell_delivery_methods": [
                        {
                          "name": "bank_account",
                          "description": "Send USD directly to the Anchor\'s bank account."
                        }
                      ],
                      "buy_delivery_methods": [
                        {
                          "name": "bank_account",
                          "description": "Have USD sent directly to your bank account."
                        }
                      ]
                 }',
            'created_at' => now(),
        ]);

        DB::table('anchor_assets')->insert([
            'schema' => 'stellar',
            'code' => 'native',
            'significant_decimals' => 7,
            'deposit_enabled' => true,
            'deposit_min_amount' => 1,
            'deposit_max_amount' => 1000000,
            'withdrawal_enabled' => true,
            'withdrawal_min_amount' => 1,
            'withdrawal_max_amount' => 1000000,
            'sep38_enabled' => true,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<array-key, Sep31AssetInfo>
     */
    private function composeSep31Info():array {
        $usdcAssetCode = config('stellar.assets.usdc_asset_code');
        $usdcAssetIssuerId = config('stellar.assets.usdc_asset_issuer_id');

        $usdc = new IdentificationFormatAsset(
            schema: IdentificationFormatAsset::ASSET_SCHEMA_STELLAR,
            code: $usdcAssetCode,
            issuer: $usdcAssetIssuerId,
        );

        $jpycAssetCode = config('stellar.assets.jpyc_asset_code');
        $jpycAssetIssuerId = config('stellar.assets.jpyc_asset_issuer_id');
        $jpyc = new IdentificationFormatAsset(
            schema: IdentificationFormatAsset::ASSET_SCHEMA_STELLAR,
            code: $jpycAssetCode,
            issuer: $jpycAssetIssuerId,
        );

        $senderTypes = [
            new Sep12Type(
                name:'sep31-sender',
                description: 'U.S. citizens limited to sending payments of less than $10,000 in value',
            ),
            new Sep12Type(
                name:'sep31-large-sender',
                description: 'U.S. citizens that do not have sending limits',
            ),
            new Sep12Type(
                name:'sep31-foreign-sender',
                description: 'non-U.S. citizens sending payments of less than $10,000 in value',
            ),
        ];

        $receiverTypes = [
            new Sep12Type(
                name:'sep31-receiver',
                description: 'U.S. citizens receiving USD',
            ),
            new Sep12Type(
                name:'sep31-foreign-receiver',
                description: 'non-U.S. citizens receiving USD',
            ),
        ];

        $usdcSep31Asset = new Sep31AssetInfo(
            asset: $usdc,
            sep12SenderTypes: $senderTypes,
            sep12ReceiverTypes: $receiverTypes,
            quotesSupported: true,
            quotesRequired: false,
        );

        $jpycSep31Asset = new Sep31AssetInfo(
            asset: $jpyc,
            sep12SenderTypes: $senderTypes,
            sep12ReceiverTypes: $receiverTypes,
            quotesSupported: true,
            quotesRequired: false,
        );

        return [
            $usdcAssetCode => $usdcSep31Asset,
            $jpycAssetCode => $jpycSep31Asset,
        ];
    }
}
