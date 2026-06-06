<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthEmailValidationTest extends TestCase
{
    public function test_login_rejects_email_with_crlf_characters(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => "user@example.com\r\ncc:attacker@example.com",
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_password_reset_rejects_email_with_crlf_characters(): void
    {
        $response = $this->postJson('/api/password/forgot', [
            'email' => "user@example.com\nreply-to:attacker@example.com",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
