<?php

use App\Filament\Resources\LicenseActivations\LicenseActivationResource;
use App\Filament\Resources\LicenseActivations\Pages\CreateLicenseActivation;
use App\Filament\Resources\LicenseActivations\Pages\EditLicenseActivation;
use App\Filament\Resources\LicenseActivations\Pages\ListLicenseActivations;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getDefaultPanel());
});

describe('LicenseActivation Resource', function () {
    it('can render the list page', function () {
        $this->get(LicenseActivationResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        $this->get(LicenseActivationResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $activation = LicenseActivation::factory()->create();

        $this->get(LicenseActivationResource::getUrl('edit', ['record' => $activation]))
            ->assertSuccessful();
    });
});

describe('LicenseActivation List', function () {
    it('can list activations', function () {
        $activations = LicenseActivation::factory()->count(5)->create();

        Livewire::test(ListLicenseActivations::class)
            ->assertCanSeeTableRecords($activations);
    });

    it('can search activations by domain', function () {
        $activation = LicenseActivation::factory()->create(['domain' => 'myuniquedomain.com']);
        $otherActivation = LicenseActivation::factory()->create(['domain' => 'other.com']);

        Livewire::test(ListLicenseActivations::class)
            ->searchTable('myuniquedomain.com')
            ->assertCanSeeTableRecords([$activation])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });

    it('can search activations by ip address', function () {
        $activation = LicenseActivation::factory()->create(['ip_address' => '192.168.1.100']);
        $otherActivation = LicenseActivation::factory()->create(['ip_address' => '10.0.0.1']);

        Livewire::test(ListLicenseActivations::class)
            ->searchTable('192.168.1.100')
            ->assertCanSeeTableRecords([$activation])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });

    it('can filter activations by active status', function () {
        $activeActivation = LicenseActivation::factory()->active()->create();
        $deactivatedActivation = LicenseActivation::factory()->deactivated()->create();

        Livewire::test(ListLicenseActivations::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeActivation])
            ->assertCanNotSeeTableRecords([$deactivatedActivation]);
    });

    it('can filter activations by deactivated status', function () {
        $activeActivation = LicenseActivation::factory()->active()->create();
        $deactivatedActivation = LicenseActivation::factory()->deactivated()->create();

        Livewire::test(ListLicenseActivations::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$deactivatedActivation])
            ->assertCanNotSeeTableRecords([$activeActivation]);
    });

    it('can filter activations by license', function () {
        $license = License::factory()->create();
        $activation = LicenseActivation::factory()->create(['license_id' => $license->id]);
        $otherActivation = LicenseActivation::factory()->create();

        Livewire::test(ListLicenseActivations::class)
            ->filterTable('license', $license->id)
            ->assertCanSeeTableRecords([$activation])
            ->assertCanNotSeeTableRecords([$otherActivation]);
    });

    it('can sort activations by activated_at', function () {
        $oldActivation = LicenseActivation::factory()->create(['activated_at' => now()->subDays(30)]);
        $newActivation = LicenseActivation::factory()->create(['activated_at' => now()]);

        Livewire::test(ListLicenseActivations::class)
            ->sortTable('activated_at', 'desc')
            ->assertCanSeeTableRecords([$newActivation, $oldActivation], inOrder: true);
    });
});

describe('LicenseActivation Create', function () {
    it('can create an activation', function () {
        $license = License::factory()->create();

        Livewire::test(CreateLicenseActivation::class)
            ->fillForm([
                'license_id' => $license->id,
                'domain' => 'newdomain.com',
                'ip_address' => '192.168.1.1',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('license_activations', [
            'license_id' => $license->id,
            'domain' => 'newdomain.com',
            'ip_address' => '192.168.1.1',
            'is_active' => true,
        ]);
    });

    it('requires a license', function () {
        Livewire::test(CreateLicenseActivation::class)
            ->fillForm([
                'domain' => 'test.com',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['license_id' => 'required']);
    });

    it('requires a domain', function () {
        $license = License::factory()->create();

        Livewire::test(CreateLicenseActivation::class)
            ->fillForm([
                'license_id' => $license->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['domain' => 'required']);
    });
});

describe('LicenseActivation Edit', function () {
    it('can update an activation', function () {
        $activation = LicenseActivation::factory()->create();

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->id])
            ->fillForm([
                'domain' => 'updated-domain.com',
                'ip_address' => '10.0.0.1',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $activation->refresh();
        expect($activation->domain)->toBe('updated-domain.com')
            ->and($activation->ip_address)->toBe('10.0.0.1');
    });

    it('can deactivate an activation', function () {
        $activation = LicenseActivation::factory()->active()->create();

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->id])
            ->fillForm([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => 'No longer needed',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $activation->refresh();
        expect($activation->is_active)->toBeFalse()
            ->and($activation->deactivation_reason)->toBe('No longer needed');
    });

    it('can reactivate an activation', function () {
        $activation = LicenseActivation::factory()->deactivated()->create();

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->id])
            ->fillForm([
                'is_active' => true,
                'deactivated_at' => null,
                'deactivation_reason' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $activation->refresh();
        expect($activation->is_active)->toBeTrue()
            ->and($activation->deactivated_at)->toBeNull()
            ->and($activation->deactivation_reason)->toBeNull();
    });

    it('populates form with existing data', function () {
        $activation = LicenseActivation::factory()->create([
            'domain' => 'existing-domain.com',
            'ip_address' => '1.2.3.4',
        ]);

        Livewire::test(EditLicenseActivation::class, ['record' => $activation->id])
            ->assertFormSet([
                'domain' => 'existing-domain.com',
                'ip_address' => '1.2.3.4',
            ]);
    });
});

describe('LicenseActivation Model', function () {
    it('belongs to a license', function () {
        $license = License::factory()->create();
        $activation = LicenseActivation::factory()->create(['license_id' => $license->id]);

        expect($activation->license->id)->toBe($license->id);
    });

    it('can check if active', function () {
        $activeActivation = LicenseActivation::factory()->active()->create();
        $deactivatedActivation = LicenseActivation::factory()->deactivated()->create();

        expect($activeActivation->isActive())->toBeTrue()
            ->and($deactivatedActivation->isActive())->toBeFalse();
    });

    it('can deactivate with reason', function () {
        $activation = LicenseActivation::factory()->active()->create();

        $activation->deactivate('License expired');

        expect($activation->is_active)->toBeFalse()
            ->and($activation->deactivated_at)->not->toBeNull()
            ->and($activation->deactivation_reason)->toBe('License expired');
    });

    it('can reactivate', function () {
        $activation = LicenseActivation::factory()->deactivated('Test reason')->create();

        $activation->reactivate();

        expect($activation->is_active)->toBeTrue()
            ->and($activation->deactivated_at)->toBeNull()
            ->and($activation->deactivation_reason)->toBeNull();
    });

    it('auto-sets activated_at on creation', function () {
        $license = License::factory()->create();
        $activation = LicenseActivation::create([
            'license_id' => $license->id,
            'domain' => 'test.com',
        ]);

        expect($activation->activated_at)->not->toBeNull();
    });
});

describe('License Activations Relationship', function () {
    it('license has many activations', function () {
        $license = License::factory()->create();
        LicenseActivation::factory()->count(3)->create(['license_id' => $license->id]);

        expect($license->activations)->toHaveCount(3);
    });

    it('license can get active activation', function () {
        $license = License::factory()->create();
        $activeActivation = LicenseActivation::factory()->active()->create(['license_id' => $license->id]);
        LicenseActivation::factory()->deactivated()->create(['license_id' => $license->id]);

        expect($license->activeActivation)->not->toBeNull()
            ->and($license->activeActivation->id)->toBe($activeActivation->id);
    });
});
