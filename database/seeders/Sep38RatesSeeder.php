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
        DB::table('sep38_rates')->insert([
            'sell_asset' => 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'buy_asset' => 'stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U',
            'rate' => 0.0066
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'buy_asset' => 'iso4217:USD',
            'rate' => 1
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U',
            'buy_asset' => 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'rate' => 151.79
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U',
            'buy_asset' => 'iso4217:USD',
            'rate' => 151.79
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'iso4217:USD',
            'buy_asset' => 'stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U',
            'rate' => 0.0066
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'iso4217:USD',
            'buy_asset' => 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'rate' => 1.0
        ]);

        DB::table('sep38_rates')->insert([
            'sell_asset' => 'stellar:native',
            'buy_asset' => 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'rate' => 7.5
        ]);

    }
}
