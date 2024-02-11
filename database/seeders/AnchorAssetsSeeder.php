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
            'code' => 'ART',
            'issuer' => 'GDD4AM7ZITM6VIJBF6GFA6GCYY5EKMZ77OKYCLWGQYXNAK3KABDUOART',
            'schema' => 'stellar',
            'deposit_enabled' => true,
            'deposit_fee_fixed' => 1.0,
            'withdrawal_enabled' => true,
            'withdrawal_fee_fixed' => 1.0,
        ]);

        DB::table('anchor_assets')->insert([
            'code' => 'USDC',
            'issuer' => 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5',
            'schema' => 'stellar',
            'deposit_enabled' => true,
            'deposit_max_amount' => 1000.0,
            'withdrawal_enabled' => true,
        ]);
    }
}
