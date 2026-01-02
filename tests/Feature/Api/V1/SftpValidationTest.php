<?php

use App\Models\License;
use App\Models\Product;

beforeEach(function () {
    $this->product = Product::factory()->create(['active' => true]);
    $this->license = License::factory()->for($this->product)->active()->create();
});

describe('SFTP Validation API', function () {
    it('requires license key in header', function () {
        $response = $this->postJson('/api/v1/sftp/validate', [
            'sftp_host' => 'sftp.example.com',
            'sftp_port' => 22,
            'sftp_username' => 'user',
            'sftp_password' => 'password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'X-License-Key header is required.',
            ]);
    });

    it('rejects invalid license key', function () {
        $response = $this->postJson('/api/v1/sftp/validate', [
            'sftp_host' => 'sftp.example.com',
            'sftp_port' => 22,
            'sftp_username' => 'user',
            'sftp_password' => 'password',
        ], [
            'X-License-Key' => 'invalid-key',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid license key.',
            ]);
    });

    it('rejects inactive license', function () {
        $license = License::factory()->for($this->product)->revoked()->create([
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->postJson('/api/v1/sftp/validate', [
            'sftp_host' => 'sftp.example.com',
            'sftp_port' => 22,
            'sftp_username' => 'user',
            'sftp_password' => 'password',
        ], [
            'X-License-Key' => $license->license_key,
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'License is not active.',
            ]);
    });

    it('rejects expired license', function () {
        $license = License::factory()->for($this->product)->expired()->create();

        $response = $this->postJson('/api/v1/sftp/validate', [
            'sftp_host' => 'sftp.example.com',
            'sftp_port' => 22,
            'sftp_username' => 'user',
            'sftp_password' => 'password',
        ], [
            'X-License-Key' => $license->license_key,
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'License has expired.',
            ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/sftp/validate', [], [
            'X-License-Key' => $this->license->license_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sftp_host', 'sftp_port', 'sftp_username', 'sftp_password']);
    });

    it('validates port range', function () {
        $response = $this->postJson('/api/v1/sftp/validate', [
            'sftp_host' => 'sftp.example.com',
            'sftp_port' => 99999,
            'sftp_username' => 'user',
            'sftp_password' => 'password',
        ], [
            'X-License-Key' => $this->license->license_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sftp_port']);
    });

    it('returns error for invalid SFTP host', function () {
        $response = $this->postJson('/api/v1/sftp/validate', [
            'sftp_host' => 'invalid-nonexistent-host-12345.example.com',
            'sftp_port' => 22,
            'sftp_username' => 'user',
            'sftp_password' => 'password',
        ], [
            'X-License-Key' => $this->license->license_key,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Unable to connect to SFTP server.',
            ]);
    });
});
