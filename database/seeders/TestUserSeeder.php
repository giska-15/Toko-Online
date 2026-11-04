<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('123456');
        
        DB::table('users')->insert([
            'name' => 'Developer NAMIRA',
            'email' => 'giskaauramp@gmail.com',
            'password' => $password,
            'role' => 'developer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Owner NAMIRA',
            'email' => 'owner@namira.com',
            'password' => $password,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Admin NAMIRA',
            'email' => 'admin@namira.com',
            'password' => $password,
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'User NAMIRA',
            'email' => 'user@namira.com',
            'password' => $password,
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Set default BASE_URL
        DB::table('settings')->updateOrInsert(
            ['key' => 'base_url'],
            ['value' => 'https://358f-103-3-222-140.ngrok-free.app', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
