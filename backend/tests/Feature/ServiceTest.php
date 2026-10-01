<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_create_service(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);
        $response = $this->actingAs($provider)
            ->postJson('/api/services', [
                'name' => 'Oil Change',
                'price' => 5000,
                'duration' => 60,
            ]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('services', [
            'user_id' => $provider->id,
            'name' => 'Oil Change',
            'price' => 5000,
            'duration' => 60,
        ]);
    }
    public function test_customer_cannot_create_service(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/services', [
                'name' => 'Oil Change',
                'price' => 5000,
                'duration' => 60,
            ]);

        $response->assertStatus(403);
    }
    public function test_provider_cannot_create_service_with_invalid_data(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->postJson('/api/services', [
                'name' => '',
                'price' => -500,
                'duration' => 0,
            ]);

        $response->assertStatus(422);
    }
    public function test_provider_can_only_see_their_own_services(): void
    {
        $provider1 = User::factory()->create([
            'role' => 'provider',
        ]);
        $provider2 = User::factory()->create([
            'role' => 'provider',
        ]);

        // Create services for both providers
        $this->actingAs($provider1)->postJson('/api/services', [
            'name' => 'Service 1',
            'price' => 1000,
            'duration' => 30,
        ]);
        $this->actingAs($provider2)->postJson('/api/services', [
            'name' => 'Service 2',
            'price' => 2000,
            'duration' => 60,
        ]);

        // Check that provider1 can only see their own service
        $response = $this->actingAs($provider1)->getJson('/api/services');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'services');
        $response->assertJsonFragment(['name' => 'Service 1']);
        $response->assertJsonMissing(['name' => 'Service 2']);
    }
    public function test_provider_can_view_their_own_service(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'name' => 'Oil Change',
        ]);

        $response = $this->actingAs($provider)
            ->getJson("/api/services/{$service->id}");

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => 'Oil Change',
        ]);
    }
    public function test_provider_cannot_view_another_providers_service(): void
    {
        $provider1 = User::factory()->create([
            'role' => 'provider',
        ]);
        $provider2 = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider2->id,
            'name' => 'Oil Change',
        ]);

        $response = $this->actingAs($provider1)
            ->getJson("/api/services/{$service->id}");

        $response->assertStatus(403);
    }
    public function test_provider_cannot_view_nonexistent_service(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $response = $this->actingAs($provider)
            ->getJson('/api/services/999');

        $response->assertStatus(404);
    }
    public function test_provider_can_update_their_own_service(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'name' => 'Oil Change',
            'price' => 5000,
            'duration' => 60,
        ]);

        $response = $this->actingAs($provider)
            ->putJson("/api/services/{$service->id}", [
                'name' => 'Tire Rotation',
                'price' => 6000,
                'duration' => 45,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Tire Rotation',
            'price' => 6000,
            'duration' => 45,
        ]);
    }
    public function test_provider_cannot_update_another_providers_service(): void
    {
        $provider1 = User::factory()->create([
            'role' => 'provider',
        ]);

        $provider2 = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider2->id,
            'name' => 'Oil Change',
            'price' => 5000,
            'duration' => 60,
        ]);

        $response = $this->actingAs($provider1)
            ->putJson("/api/services/{$service->id}", [
                'name' => 'Hacked Service',
                'price' => 9999,
                'duration' => 120,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'user_id' => $provider2->id,
            'name' => 'Oil Change',
            'price' => 5000,
            'duration' => 60,
        ]);
    }
    public function test_provider_can_delete_their_own_service(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'name' => 'Oil Change',
        ]);

        $response = $this->actingAs($provider)
            ->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }
    public function test_provider_cannot_delete_another_providers_service(): void
    {
        $provider1 = User::factory()->create([
            'role' => 'provider',
        ]);

        $provider2 = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider2->id,
            'name' => 'Oil Change',
        ]);

        $response = $this->actingAs($provider1)
            ->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'user_id' => $provider2->id,
            'name' => 'Oil Change',
        ]);
    }
    public function test_user_cannot_update_service_without_any_fields(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
        ]);

        $response = $this->actingAs($provider)
            ->putJson("/api/services/{$service->id}", []);

        $response->assertStatus(422);
    }
    public function test_provider_can_update_only_service_name(): void
    {
        $provider = User::factory()->create([
            'role' => 'provider',
        ]);

        $service = Service::factory()->create([
            'user_id' => $provider->id,
            'name' => 'Old Name',
            'price' => 5000,
            'duration' => 60,
        ]);

        $response = $this->actingAs($provider)
            ->putJson("/api/services/{$service->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200);

        $response->assertJsonPath(
            'service.name',
            'New Name'
        );

        $response->assertJsonPath(
            'service.price',
            5000
        );

        $response->assertJsonPath(
            'service.duration',
            60
        );
    }
}
