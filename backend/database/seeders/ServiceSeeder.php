<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $provider = User::where('email', 'provider@test.sk')->firstOrFail();

        Service::create([
            'user_id' => $provider->id,
            'name' => 'Oil Change',
            'price' => 5000,
            'duration' => 60,
        ]);

        Service::create([
            'user_id' => $provider->id,
            'name' => 'Brake Inspection',
            'price' => 3000,
            'duration' => 30,
        ]);

        Service::create([
            'user_id' => $provider->id,
            'name' => 'Full Service',
            'price' => 12000,
            'duration' => 120,
        ]);
    }
}
