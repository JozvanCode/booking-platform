<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_available_slots(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        BusinessHour::create([
            'user_id' => $provider->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            "/api/services/{$service->id}/availability?date=2026-10-05"
        );

        $response
            ->assertOk()
            ->assertJsonPath('date', '2026-10-05')
            ->assertJsonPath('service.duration', 60)
            ->assertJsonPath('available_slots', [
                '08:00',
                '09:00',
                '10:00',
                '11:00',
                '13:00',
                '14:00',
                '15:00',
            ]);
    }

    public function test_booked_slot_is_not_available(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        BusinessHour::create([
            'user_id' => $provider->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => '2026-10-05 09:00:00',
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            "/api/services/{$service->id}/availability?date=2026-10-05"
        );

        $response
            ->assertOk()
            ->assertJsonMissing(['09:00']);
    }

    public function test_break_is_not_available(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        BusinessHour::create([
            'user_id' => $provider->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            "/api/services/{$service->id}/availability?date=2026-10-05"
        );

        $response
            ->assertOk()
            ->assertJsonMissing(['12:00']);
    }

    public function test_day_without_business_hours_has_no_available_slots(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        Sanctum::actingAs($customer);

        // Tuesday - no business hours configured.
        $response = $this->getJson(
            "/api/services/{$service->id}/availability?date=2026-10-06"
        );

        $response
            ->assertOk()
            ->assertJsonPath('available_slots', []);
    }
}
