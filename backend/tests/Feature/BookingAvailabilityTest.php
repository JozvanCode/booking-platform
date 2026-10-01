<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;
    private User $customer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->service = Service::factory()->create([
            'user_id' => $this->provider->id,
            'duration' => 60,
        ]);

        foreach (range(1, 5) as $day) {
            BusinessHour::create([
                'user_id' => $this->provider->id,
                'day_of_week' => $day,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
            ]);
        }

        Sanctum::actingAs($this->customer);
    }

    public function test_customer_can_create_booking_in_available_slot(): void
    {
        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->service->id,
            'start_at' => '2026-10-05 10:00',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('booking.service_id', $this->service->id)
            ->assertJsonPath('booking.status', 'pending');
    }

    public function test_customer_cannot_create_booking_in_already_booked_slot(): void
    {
        Booking::create([
            'user_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'start_at' => '2026-10-05 10:00:00',
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->service->id,
            'start_at' => '2026-10-05 10:00',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'The selected time is not available.',
            ]);
    }

    public function test_customer_cannot_create_booking_during_break(): void
    {
        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->service->id,
            'start_at' => '2026-10-05 12:00',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'The selected time is not available.',
            ]);
    }

    public function test_customer_cannot_create_booking_outside_business_hours(): void
    {
        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->service->id,
            'start_at' => '2026-10-05 17:00',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'The selected time is not available.',
            ]);
    }

    public function test_customer_cannot_create_booking_on_day_without_business_hours(): void
    {
        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->service->id,
            'start_at' => '2026-10-10 10:00',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'The selected time is not available.',
            ]);
    }
}
