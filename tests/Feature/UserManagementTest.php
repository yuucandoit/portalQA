<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_access_user_management(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'admin', 'status' => 'Active']);
        }

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('User & Access Management');
        $response->assertSee('Daftar Pengguna');
    }

    public function test_member_cannot_access_user_management(): void
    {
        $member = User::where('role', 'member')->first();
        if (!$member) {
            $member = User::factory()->create(['role' => 'member', 'status' => 'Active']);
        }

        $response = $this->actingAs($member)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_toggle_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'Active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'Active']);

        $response = $this->actingAs($admin)->post("/admin/users/{$member->id}/role", [
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertEquals('admin', $member->fresh()->role);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'Active']);

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/role", [
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertEquals('admin', $admin->fresh()->role);
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'Active']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Budi Tester',
            'email' => 'budi@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'member',
            'status' => 'Active',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', ['email' => 'budi@test.com', 'role' => 'member']);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'Active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'Active']);

        $response = $this->actingAs($admin)->put("/admin/users/{$member->id}", [
            'name' => 'Member Updated',
            'email' => $member->email,
            'role' => 'admin',
            'status' => 'Active',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertEquals('Member Updated', $member->fresh()->name);
        $this->assertEquals('admin', $member->fresh()->role);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'Active']);

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $response->assertSessionHasErrors(['delete']);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
