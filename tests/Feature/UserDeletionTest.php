<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_delete_another_admin()
    {
        // Create an admin user who is the deleter
        $deleter = User::factory()->create();
        $deleter->assignRole('admin');

        // Create another admin user to be deleted
        $targetUser = User::factory()->create();
        $targetUser->assignRole('admin');

        // Execute delete request
        $response = $this->actingAs($deleter)->delete(route('users.destroy', $targetUser->id));

        // Assert redirect and success message
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'User deleted successfully.');

        // Assert target user is deleted from database
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_non_admin_cannot_delete_admin()
    {
        // Create a non-admin user (e.g., admin_provinsi) who is the deleter
        $deleter = User::factory()->create();
        $deleter->assignRole('admin_provinsi');

        // Create an admin user to be deleted
        $targetUser = User::factory()->create();
        $targetUser->assignRole('admin');

        // Execute delete request
        $response = $this->actingAs($deleter)->delete(route('users.destroy', $targetUser->id));

        // Assert redirect back with error message
        $response->assertSessionHas('error', 'User dengan role admin tidak dapat dihapus oleh role lain.');

        // Assert target user still exists in database
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
    }

    public function test_non_admin_can_delete_non_admin()
    {
        // Create a non-admin user (e.g. admin_provinsi, who by default has permission to delete users)
        $deleter = User::factory()->create();
        $deleter->assignRole('admin_provinsi');

        // Create a non-admin target user (e.g. pelaksana role)
        $targetUser = User::factory()->create();
        $targetUser->assignRole('pelaksana');

        // Execute delete request
        $response = $this->actingAs($deleter)->delete(route('users.destroy', $targetUser->id));

        // Assert redirect and success
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'User deleted successfully.');

        // Assert target user is deleted
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }
}
