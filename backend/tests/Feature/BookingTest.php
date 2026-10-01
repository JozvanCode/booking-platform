<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function createBusinessHours(User $provider): void
    {
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

    public function test_two_users_cannot_book_the_same_slot(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user1 = User::factory()->create([
            'role' => 'customer',
        ]);

        $user2 = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($provider);

        $response1 = $this->actingAs($user1)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response1->assertStatus(201);

        $response2 = $this->actingAs($user2)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response2->assertStatus(409);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_overlapping_bookings_are_rejected(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user1 = User::factory()->create([
            'role' => 'customer',
        ]);

        $user2 = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 30,
        ]);

        $this->createBusinessHours($provider);

        $response1 = $this->actingAs($user1)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response1->assertStatus(201);

        $response2 = $this->actingAs($user2)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:15:00',
            ]);

        $response2->assertStatus(409);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_can_start_when_previous_booking_ends(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user1 = User::factory()->create([
            'role' => 'customer',
        ]);

        $user2 = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($provider);

        $response1 = $this->actingAs($user1)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response1->assertStatus(201);

        $response2 = $this->actingAs($user2)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 11:00:00',
            ]);

        $response2->assertStatus(201);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_user_cannot_view_another_users_booking(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user1 = User::factory()->create([
            'role' => 'customer',
        ]);

        $user2 = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
        ]);

        $this->createBusinessHours($provider);

        $response = $this->actingAs($user1)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 14:00:00',
            ]);

        $response->assertStatus(201);

        $bookingId = $response->json('booking.id');

        $response = $this->actingAs($user2)
            ->getJson("/api/bookings/{$bookingId}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_booking(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($provider);

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response->assertStatus(201);

        $bookingId = $response->json('booking.id');

        $response = $this->actingAs($user)
            ->putJson("/api/bookings/{$bookingId}", [
                'start_at' => '2026-09-25 11:00:00',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'start_at' => '2026-09-25 11:00:00',
        ]);
    }

    public function test_booking_cannot_be_updated_to_an_overlapping_time(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user1 = User::factory()->create([
            'role' => 'customer',
        ]);

        $user2 = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($provider);

        $response1 = $this->actingAs($user1)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response1->assertStatus(201);

        $response2 = $this->actingAs($user2)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 13:00:00',
            ]);

        $response2->assertStatus(201);

        $booking2Id = $response2->json('booking.id');

        $response = $this->actingAs($user2)
            ->putJson("/api/bookings/{$booking2Id}", [
                'start_at' => '2026-09-25 10:00:00',
            ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking2Id,
            'start_at' => '2026-09-25 13:00:00',
        ]);
    }

    public function test_user_can_cancel_their_booking(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($provider);

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_at' => '2026-09-25 15:00:00',
            ]);

        $response->assertStatus(201);

        $bookingId = $response->json('booking.id');

        $response = $this->actingAs($user)
            ->deleteJson("/api/bookings/{$bookingId}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'cancelled',
        ]);
    }

    public function test_user_factory_creates_customer_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('customer', $user->role);
    }

    public function test_provider_can_access_provider_route(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->getJson('/api/provider-test');

        $response->assertStatus(200);
    }

    public function test_customer_cannot_access_provider_route(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)
            ->getJson('/api/provider-test');

        $response->assertStatus(403);
    }

    public function test_provider_can_see_bookings_for_their_services(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
        ]);

        $customer = User::factory()->create();

        $booking = $customer->bookings()->create([
            'service_id' => $service->id,
            'start_at' => '2026-09-25 16:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider)
            ->getJson('/api/bookings');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $booking->id,
            'service_id' => $service->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_provider_cannot_see_bookings_for_another_provider(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $otherProvider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $otherProvider->id,
        ]);

        $customer = User::factory()->create();

        $booking = $customer->bookings()->create([
            'service_id' => $service->id,
            'start_at' => '2026-09-25 16:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider)
            ->getJson('/api/bookings');

        $response->assertStatus(200);

        $response->assertJsonMissing([
            'id' => $booking->id,
        ]);
    }

    public function test_user_cannot_update_booking_without_any_fields(): void
    {
        $user = User::factory()->create();

        $service = Service::factory()->create();

        $booking = $user->bookings()->create([
            'service_id' => $service->id,
            'start_at' => '2026-09-25 16:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/bookings/{$booking->id}", []);

        $response->assertStatus(422);
    }

    public function test_user_can_update_only_start_at(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($provider);

        $booking = $user->bookings()->create([
            'service_id' => $service->id,
            'start_at' => '2026-09-25 14:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/bookings/{$booking->id}", [
                'start_at' => '2026-09-25 15:00:00',
            ]);

        $response->assertStatus(200);

        $response->assertJsonPath(
            'booking.start_at',
            '2026-09-25T15:00:00.000000Z'
        );
    }

    public function test_concurrent_bookings_for_same_service_do_not_both_succeed(): void
    {
        $serviceOwner = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $serviceOwner->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($serviceOwner);

        $customerA = User::factory()->create([
            'role' => 'customer',
        ]);

        $customerB = User::factory()->create([
            'role' => 'customer',
        ]);

        $payload = [
            'service_id' => $service->id,
            'start_at' => '2026-09-25 10:00:00',
        ];

        $responseA = $this->actingAs($customerA)
            ->postJson('/api/bookings', $payload);

        $responseA->assertStatus(201);

        $responseB = $this->actingAs($customerB)
            ->postJson('/api/bookings', $payload);

        $responseB->assertStatus(409);

        $this->assertDatabaseCount('bookings', 1);
    }
    public function test_provider_can_confirm_customer_booking(): void
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

        $this->createBusinessHours($provider);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => '2026-10-05 10:00:00',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($provider);

        $response = $this->patchJson(
            "/api/bookings/{$booking->id}/confirm"
        );

        $response
            ->assertOk()
            ->assertJsonPath('booking.status', 'confirmed');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }
    public function test_provider_cannot_confirm_another_providers_booking(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $otherProvider = User::factory()->create([
            'role' => 'provider',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $service = Service::factory()->create([
            'user_id' => $otherProvider->id,
            'duration' => 60,
        ]);

        $this->createBusinessHours($otherProvider);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => '2026-10-05 10:00:00',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($provider);

        $this->patchJson("/api/bookings/{$booking->id}/confirm")
            ->assertForbidden();
    }
    public function test_provider_can_complete_confirmed_booking(): void
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

        $this->createBusinessHours($provider);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => '2026-10-05 10:00:00',
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($provider);

        $this->patchJson("/api/bookings/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('booking.status', 'completed');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
    }
}
