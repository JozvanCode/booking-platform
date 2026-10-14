<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => 'customer',
            ]),
            'service_id' => Service::factory(),
            'start_at' => now()->addDays(7)->setTime(10, 0),
            'status' => 'pending',
        ];
    }
}
