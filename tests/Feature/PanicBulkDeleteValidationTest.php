<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PanicBulkDeleteValidationTest extends TestCase
{
    public function test_admin_panic_bulk_delete_returns_validation_errors(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        try {
            Sanctum::actingAs($admin);

            $response = $this->postJson('/api/admin/panic/bulk-delete', [
                'start_date' => '2026-01-02',
                'end_date' => '2026-01-01',
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['end_date']);
        } finally {
            $admin->delete();
        }
    }
}
