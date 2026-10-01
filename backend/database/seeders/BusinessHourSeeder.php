<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    public function run(): void
    {
        $provider = User::where('email', 'provider@test.sk')->firstOrFail();

        foreach (range(1, 5) as $day) {
            BusinessHour::create([
                'user_id' => $provider->id,
                'day_of_week' => $day,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
            ]);
        }
    }
}
