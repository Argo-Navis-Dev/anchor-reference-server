<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnchorAssetsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('anchor_assets')->insert([
            'schema' => 'stellar',
            'code' => 'USDC',
            'issuer' => 'GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'significant_decimals' => 2,
            'deposit_enabled' => true,
            'deposit_min_amount' => 1.0,
            'deposit_max_amount' => 1000.0,
            'withdrawal_enabled' => true,
            'withdrawal_min_amount' => 1.0,
            'withdrawal_max_amount' => 1000.0,
            'sep38_enabled' => true
        ]);

        DB::table('anchor_assets')->insert([
            'schema' => 'stellar',
            'code' => 'JPYC',
            'issuer' => 'GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U',
            'significant_decimals' => 2,
            'deposit_enabled' => true,
            'deposit_min_amount' => 1.0,
            'deposit_max_amount' => 1000000.0,
            'withdrawal_enabled' => true,
            'withdrawal_min_amount' => 1.0,
            'withdrawal_max_amount' => 1000000.0,
            'sep38_enabled' => true,
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
                          "name": "WIRE",
                          "description": "Send USD directly to the Anchor\'s bank account."
                        }
                      ],
                      "buy_delivery_methods": [
                        {
                          "name": "WIRE",
                          "description": "Have USD sent directly to your bank account."
                        }
                      ]
                 }'
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
        ]);
    }
}
