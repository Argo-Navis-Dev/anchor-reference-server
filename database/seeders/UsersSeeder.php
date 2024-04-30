<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default user
        DB::table('users')->insert([
            'name' => 'Anchor Admin',
            'email' => 'anchor.admin@argo-navis.dev',
            'password' => bcrypt('AnchorAdmin2023!'),
            'created_at' => now(),
        ]);
    }
}
