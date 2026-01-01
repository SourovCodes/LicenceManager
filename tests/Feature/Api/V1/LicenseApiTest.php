<?php

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Product;

beforeEach(function () {
    $this->product = Product::factory()->create(['active' => true, 'slug' => 'my-plugin']);
});

describe('Activate License', function () {
    it('activates a new license on a domain', function () {
        $license = License::factory()
            ->for($this->product)
            ->unactivated()
            ->create(['validity_days' => 365]);

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'License activated successfully.',
            ]);

        $license->refresh();
        expect($license->status)->toBe(LicenseStatus::Active)
            ->and($license->activated_at)->not->toBeNull()
            ->and($license->expires_at)->not->toBeNull()
            ->and($license->activeActivation->domain)->toBe('example.com');
    });

    it('normalizes domain on activation', function () {
        $license = License::factory()
            ->for($this->product)
            ->unactivated()
            ->create();

        $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'https://www.EXAMPLE.com/',
            'product_slug' => $this->product->slug,
        ]);

        expect($license->activeActivation->domain)->toBe('example.com');
    });

    it('returns already active message for same domain', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create();

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'License is already active on this domain.',
            ]);
    });

    it('allows domain change when quota available', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create([
                'max_domain_changes' => 3,
                'domain_changes_used' => 0,
            ]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'old-domain.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'new-domain.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertOk();

        $license->refresh();
        expect($license->domain_changes_used)->toBe(1)
            ->and($license->activeActivation->domain)->toBe('new-domain.com');
    });

    it('rejects domain change when quota exhausted', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create([
                'max_domain_changes' => 2,
                'domain_changes_used' => 2,
            ]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'old-domain.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'new-domain.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Maximum domain changes reached. Contact support.',
            ]);
    });

    it('rejects revoked license', function () {
        $license = License::factory()
            ->for($this->product)
            ->revoked()
            ->create();

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License has been revoked.',
            ]);
    });

    it('rejects expired license', function () {
        $license = License::factory()
            ->for($this->product)
            ->expired()
            ->create();

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License has expired.',
            ]);
    });

    it('rejects invalid license key', function () {
        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => 'INVALID-KEY',
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License key not found.',
            ]);
    });

    it('rejects license for wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);
        $license = License::factory()
            ->for($otherProduct)
            ->unactivated()
            ->create();

        $response = $this->postJson('/api/v1/license/activate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'my-plugin',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License is not valid for this product.',
            ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/license/activate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['license_key', 'domain', 'product_slug']);
    });
});

describe('Validate License', function () {
    it('validates an active license on correct domain', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create(['expires_at' => now()->addDays(30)]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'License is valid.',
            ])
            ->assertJsonStructure(['expires_at', 'days_remaining']);
    });

    it('rejects license on wrong domain', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create();

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'correct-domain.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'domain' => 'wrong-domain.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License is not active on this domain.',
            ]);
    });

    it('rejects unactivated license', function () {
        $license = License::factory()
            ->for($this->product)
            ->unactivated()
            ->create();

        $response = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License is not activated.',
            ]);
    });

    it('rejects license for wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);
        $license = License::factory()
            ->for($otherProduct)
            ->active()
            ->create();

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'my-plugin',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License is not valid for this product.',
            ]);
    });
});

describe('Deactivate License', function () {
    it('deactivates an active license', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create();

        $activation = LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/deactivate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'License deactivated successfully.',
            ]);

        $activation->refresh();
        expect($activation->is_active)->toBeFalse()
            ->and($activation->deactivated_at)->not->toBeNull();
    });

    it('rejects deactivation for wrong domain', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create();

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'correct-domain.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/deactivate', [
            'license_key' => $license->license_key,
            'domain' => 'wrong-domain.com',
            'product_slug' => $this->product->slug,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'No active license found on this domain.',
            ]);
    });

    it('rejects deactivation for wrong product', function () {
        $otherProduct = Product::factory()->create(['slug' => 'other-plugin']);
        $license = License::factory()
            ->for($otherProduct)
            ->active()
            ->create();

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/deactivate', [
            'license_key' => $license->license_key,
            'domain' => 'example.com',
            'product_slug' => 'my-plugin',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'License is not valid for this product.',
            ]);
    });
});

describe('License Status', function () {
    it('returns full license status', function () {
        $license = License::factory()
            ->for($this->product)
            ->active()
            ->create([
                'max_domain_changes' => 5,
                'domain_changes_used' => 2,
            ]);

        LicenseActivation::factory()->create([
            'license_id' => $license->id,
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/license/status', [
            'license_key' => $license->license_key,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'license_key',
                    'status',
                    'product' => ['name', 'slug', 'type'],
                    'customer_name',
                    'activation' => ['domain', 'activated_at'],
                    'expires_at',
                    'days_remaining',
                    'domain_changes' => ['used', 'max', 'remaining'],
                ],
            ]);

        $data = $response->json('data');
        expect($data['domain_changes']['used'])->toBe(2)
            ->and($data['domain_changes']['max'])->toBe(5)
            ->and($data['domain_changes']['remaining'])->toBe(3);
    });

    it('returns 404 for invalid license key', function () {
        $response = $this->postJson('/api/v1/license/status', [
            'license_key' => 'INVALID-KEY',
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'License key not found.',
            ]);
    });
});
