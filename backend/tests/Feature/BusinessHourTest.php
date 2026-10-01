<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHourTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_create_business_hours(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('business_hours', [
            'user_id' => $provider->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
    }

    public function test_customer_cannot_create_business_hours(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

        $response->assertStatus(403);
    }

    public function test_provider_cannot_create_business_hours_with_invalid_day(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 8,
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

        $response->assertStatus(422);
    }

    public function test_provider_cannot_create_business_hours_with_invalid_time_format(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => 'invalid',
                'end_time' => '16:00',
            ]);

        $response->assertStatus(422);
    }
    public function test_provider_cannot_create_business_hours_for_same_day_twice(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '16:00',
            ])
            ->assertStatus(201);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('day_of_week');
    }
    public function test_provider_cannot_create_business_hours_when_start_is_after_end(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '16:00',
                'end_time' => '08:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('start_time');
    }
    public function test_provider_cannot_create_business_hours_with_incomplete_break(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'break_start' => '12:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('break_start');
    }
    public function test_provider_cannot_create_business_hours_with_break_outside_working_hours(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/business-hours', [
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'break_start' => '16:00',
                'break_end' => '17:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('break_start');
    }
}
