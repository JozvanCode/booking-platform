<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_change_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$customer->id}/role", [
            'role' => 'provider',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.role.updated',
            'subject_id' => $customer->id,
        ]);
    }

    public function test_customer_cannot_view_audit_logs(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'customer'])
        );

        $this->getJson('/api/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_admin_can_view_audit_logs(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'admin'])
        );

        $this->getJson('/api/admin/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'total']);
    }
}
