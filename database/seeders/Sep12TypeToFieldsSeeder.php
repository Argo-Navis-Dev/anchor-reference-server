<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sep12TypeToFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sep12_type_to_fields')->insert([
            'type' => 'default',
            'required_fields' => 'first_name, last_name, email_address, id_number, id_type, photo_id_front',
            'optional_fields' => 'photo_id_back',
            'created_at' => now(),
        ]);
    }
}
