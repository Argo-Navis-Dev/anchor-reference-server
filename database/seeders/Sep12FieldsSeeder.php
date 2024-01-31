<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sep12FieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sep12_fields')->insert([
            'key' => 'first_name',
            'type' => 'string',
            'desc' => "The customer's first name",
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'last_name',
            'type' => 'string',
            'desc' => "The customer's last name",
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'email_address',
            'type' => 'string',
            'desc' => "The customer's email address",
            'requires_verification' => true,
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_number',
            'type' => 'string',
            'desc' => "The customer's id number",
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_type',
            'type' => 'string',
            'desc' => "The customer's id type",
            'choices' => "Passport, ID Card, Drivers Licence",
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'photo_id_front',
            'type' => 'binary',
            'desc' => "Image of front of the customer's photo ID or passport",
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'photo_id_back',
            'type' => 'binary',
            'desc' => "Image of front of the customer's photo ID or passport",
        ]);
    }
}
