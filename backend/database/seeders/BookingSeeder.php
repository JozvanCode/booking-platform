<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@test.sk')->firstOrFail();
        $service = Service::where('name', 'Oil Change')->firstOrFail();

        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => '2026-10-05 09:00:00',
            'status' => 'confirmed',
        ]);
    }
}
