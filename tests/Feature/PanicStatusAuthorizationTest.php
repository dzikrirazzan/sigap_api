<?php

namespace Tests\Feature;

use App\Models\PanicReport;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PanicStatusAuthorizationTest extends TestCase
{
    public function test_regular_user_cannot_update_panic_status(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'no_telp' => '081234567890',
        ]);

        $panic = PanicReport::create([
            'user_id' => $user->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'location_description' => 'authorization regression test',
            'emergency_type' => 'medical',
            'status' => PanicReport::STATUS_PENDING,
        ]);

        try {
            Sanctum::actingAs($user);

            $response = $this->putJson("/api/panic/{$panic->id}/status", [
                'status' => 'resolved',
            ]);

            $response->assertStatus(403);
            $this->assertSame(PanicReport::STATUS_PENDING, $panic->fresh()->status);
        } finally {
            $panic->delete();
            $user->delete();
        }
    }
}
