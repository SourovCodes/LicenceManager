<?php

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Resources\Licenses\Pages\EditLicense;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getDefaultPanel());
});

it('can render the licenses list page', function () {
    $this->get(ListLicenses::getUrl())
        ->assertSuccessful();
});

it('can list licenses in the table', function () {
    $licenses = License::factory()->count(3)->create();

    Livewire::test(ListLicenses::class)
        ->assertCanSeeTableRecords($licenses);
});

it('can search licenses by license key', function () {
    $license = License::factory()->create(['license_key' => 'TEST-XXXX-YYYY-ZZZZ']);
    $otherLicense = License::factory()->create(['license_key' => 'DIFF-AAAA-BBBB-CCCC']);

    Livewire::test(ListLicenses::class)
        ->searchTable('TEST-XXXX')
        ->assertCanSeeTableRecords([$license])
        ->assertCanNotSeeTableRecords([$otherLicense]);
});

it('can search licenses by customer name', function () {
    $license = License::factory()->create(['customer_name' => 'John Doe']);
    $otherLicense = License::factory()->create(['customer_name' => 'Jane Smith']);

    Livewire::test(ListLicenses::class)
        ->searchTable('John Doe')
        ->assertCanSeeTableRecords([$license])
        ->assertCanNotSeeTableRecords([$otherLicense]);
});

it('can filter licenses by status', function () {
    $activeLicense = License::factory()->active()->create();
    $expiredLicense = License::factory()->expired()->create();

    Livewire::test(ListLicenses::class)
        ->filterTable('status', [LicenseStatus::Active->value])
        ->assertCanSeeTableRecords([$activeLicense])
        ->assertCanNotSeeTableRecords([$expiredLicense]);
});

it('can filter licenses by product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $license1 = License::factory()->create(['product_id' => $product1->id]);
    $license2 = License::factory()->create(['product_id' => $product2->id]);

    Livewire::test(ListLicenses::class)
        ->filterTable('product', [$product1->id])
        ->assertCanSeeTableRecords([$license1])
        ->assertCanNotSeeTableRecords([$license2]);
});

it('can render the create license page', function () {
    $this->get(CreateLicense::getUrl())
        ->assertSuccessful();
});

it('can create a license', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateLicense::class)
        ->fillForm([
            'product_id' => $product->id,
            'license_key' => 'NEW-TEST-KEY-1234',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => LicenseStatus::Active,
            'validity_days' => 365,
            'max_domain_changes' => 3,
            'domain_changes_used' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(License::class, [
        'license_key' => 'NEW-TEST-KEY-1234',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'product_id' => $product->id,
    ]);
});

it('auto-generates license key when creating a license', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateLicense::class)
        ->fillForm([
            'product_id' => $product->id,
            'status' => LicenseStatus::Active,
            'validity_days' => 365,
            'max_domain_changes' => 3,
            'domain_changes_used' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $license = License::latest()->first();
    expect($license->license_key)->not->toBeEmpty()
        ->and($license->license_key)->toMatch('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});

it('validates required fields when creating a license', function () {
    Livewire::test(CreateLicense::class)
        ->fillForm([
            'product_id' => null,
            'license_key' => '',
            'status' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['product_id' => 'required', 'license_key' => 'required', 'status' => 'required']);
});

it('validates license key uniqueness when creating a license', function () {
    License::factory()->create(['license_key' => 'EXISTING-KEY-1234']);

    $product = Product::factory()->create();

    Livewire::test(CreateLicense::class)
        ->fillForm([
            'product_id' => $product->id,
            'license_key' => 'EXISTING-KEY-1234',
            'status' => LicenseStatus::Active,
            'validity_days' => 365,
            'max_domain_changes' => 3,
            'domain_changes_used' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['license_key' => 'unique']);
});

it('can render the edit license page', function () {
    $license = License::factory()->create();

    $this->get(EditLicense::getUrl(['record' => $license]))
        ->assertSuccessful();
});

it('can retrieve license data for editing', function () {
    $license = License::factory()->create();

    Livewire::test(EditLicense::class, ['record' => $license->getRouteKey()])
        ->assertFormSet([
            'product_id' => $license->product_id,
            'license_key' => $license->license_key,
            'customer_name' => $license->customer_name,
            'customer_email' => $license->customer_email,
            'status' => $license->status,
            'validity_days' => $license->validity_days,
        ]);
});

it('can update a license', function () {
    $license = License::factory()->create();
    $newProduct = Product::factory()->create();

    Livewire::test(EditLicense::class, ['record' => $license->getRouteKey()])
        ->fillForm([
            'product_id' => $newProduct->id,
            'customer_name' => 'Updated Customer',
            'customer_email' => 'updated@example.com',
            'status' => LicenseStatus::Expired,
            'validity_days' => 180,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $license->refresh();

    expect($license->product_id)->toBe($newProduct->id)
        ->and($license->customer_name)->toBe('Updated Customer')
        ->and($license->customer_email)->toBe('updated@example.com')
        ->and($license->status)->toBe(LicenseStatus::Expired)
        ->and($license->validity_days)->toBe(180);
});

it('can revoke a license from the table', function () {
    $license = License::factory()->active()->create();

    Livewire::test(ListLicenses::class)
        ->callTableAction('revoke', $license);

    $license->refresh();

    expect($license->status)->toBe(LicenseStatus::Revoked);
});

it('can delete a license from the table', function () {
    $license = License::factory()->create();

    Livewire::test(ListLicenses::class)
        ->callTableAction('delete', $license);

    $this->assertModelMissing($license);
});

it('can bulk delete licenses', function () {
    $licenses = License::factory()->count(3)->create();

    Livewire::test(ListLicenses::class)
        ->callTableBulkAction('delete', $licenses);

    foreach ($licenses as $license) {
        $this->assertModelMissing($license);
    }
});

// Model tests
it('generates a valid license key format', function () {
    $key = License::generateLicenseKey();

    expect($key)->toMatch('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});

it('correctly identifies if license is active', function () {
    $activeLicense = License::factory()->active()->create();
    $expiredLicense = License::factory()->expired()->create();
    $revokedLicense = License::factory()->revoked()->create();

    expect($activeLicense->isActive())->toBeTrue()
        ->and($expiredLicense->isActive())->toBeFalse()
        ->and($revokedLicense->isActive())->toBeFalse();
});

it('correctly calculates remaining domain changes', function () {
    $license = License::factory()->create([
        'max_domain_changes' => 5,
        'domain_changes_used' => 2,
    ]);

    expect($license->remainingDomainChanges())->toBe(3)
        ->and($license->canChangeDomain())->toBeTrue();
});

it('correctly identifies when domain changes are exhausted', function () {
    $license = License::factory()->create([
        'max_domain_changes' => 3,
        'domain_changes_used' => 3,
    ]);

    expect($license->remainingDomainChanges())->toBe(0)
        ->and($license->canChangeDomain())->toBeFalse();
});
