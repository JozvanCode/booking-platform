<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Test Provider',
            'email' => 'provider@test.sk',
            'password' => Hash::make('password123'),
            'role' => 'provider',
        ]);

        User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.sk',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);
    }
}
