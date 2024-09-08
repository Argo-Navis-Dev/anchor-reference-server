<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sep38RatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usdcAssetCode = config('stellar.assets.usdc_asset_code');
        $usdcAssetIssuerId = config('stellar.assets.usdc_asset_issuer_id');
        $usdcSep38Asset = 'stellar:'. $usdcAssetCode . ':' . $usdcAssetIssuerId;

        $jpycAssetCode = config('stellar.assets.jpyc_asset_code');
        $jpycAssetIssuerId = config('stellar.assets.jpyc_asset_issuer_id');
        $jpycSep38Asset = 'stellar:'. $jpycAssetCode . ':' . $jpycAssetIssuerId;

        $usdSep38Fiat = 'iso4217:USD';

        DB::table('sep38_rates')->insert([
            'sell_asset' => $usdcSep38Asset,
            'buy_asset' => $jpycSep38Asset,
            'rate' => 0.0066
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => $usdcSep38Asset,
            'buy_asset' => $usdSep38Fiat,
            'rate' => 1
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => $jpycSep38Asset,
            'buy_asset' => $usdcSep38Asset,
            'rate' => 151.79
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => $jpycSep38Asset,
            'buy_asset' => $usdSep38Fiat,
            'rate' => 151.79
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => $usdSep38Fiat,
            'buy_asset' => $jpycSep38Asset,
            'rate' => 0.0066
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => $usdSep38Fiat,
            'buy_asset' => $usdcSep38Asset,
            'rate' => 1.0
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'stellar:native',
            'buy_asset' => $usdcSep38Asset,
            'rate' => 7.5
        ]);

    }
}
