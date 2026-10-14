<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_can_view_stats(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonStructure([
                'users' => ['total', 'customers', 'providers', 'admins', 'blocked'],
                'bookings' => ['total', 'pending', 'confirmed', 'completed', 'cancelled'],
                'services' => ['total'],
            ]);
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'customer'])
        );

        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_provider_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'provider'])
        );

        $this->getJson('/api/admin/bookings')->assertForbidden();
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/admin/stats')->assertUnauthorized();
    }

    public function test_admin_can_list_users(): void
    {
        $this->actingAsAdmin();

        User::factory()->count(3)->create();

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $this->actingAsAdmin();

        User::factory()->create(['role' => 'provider']);

        $this->getJson('/api/admin/users?role=provider')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_user_details(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create();

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_admin_can_change_user_role(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['role' => 'customer']);

        $this->patchJson("/api/admin/users/{$user->id}/role", [
            'role' => 'provider',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'provider',
        ]);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}/role", [
            'role' => 'customer',
        ])->assertStatus(409);
    }

    public function test_admin_cannot_block_self(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}/status", [
            'is_active' => false,
        ])->assertStatus(409);
    }

    public function test_admin_can_block_customer(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}/status", [
            'is_active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_set_invalid_role(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}/role", [
            'role' => 'superadmin',
        ])->assertUnprocessable();
    }

    public function test_admin_can_list_bookings(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/admin/bookings')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_admin_can_list_services(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/admin/services')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_admin_can_delete_service_without_bookings(): void
    {
        $this->actingAsAdmin();

        $service = Service::factory()->create();

        $this->deleteJson("/api/admin/services/{$service->id}")
            ->assertOk();

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_admin_cannot_delete_service_with_bookings(): void
    {
        $this->actingAsAdmin();

        $service = Service::factory()->create();
        Booking::factory()->create(['service_id' => $service->id]);

        $this->deleteJson("/api/admin/services/{$service->id}")
            ->assertStatus(409);
    }
}
