<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    /**
     * Activate a license on a domain.
     *
     * @return array{success: bool, message: string, license?: License, activation?: LicenseActivation}
     */
    public function activate(string $licenseKey, string $domain, string $productSlug, ?string $ipAddress = null): array
    {
        $license = License::with(['product', 'activeActivation'])
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return $this->error('License key not found.');
        }

        if ($license->product->slug !== $productSlug) {
            return $this->error('License is not valid for this product.');
        }

        if ($license->status === LicenseStatus::Revoked) {
            return $this->error('License has been revoked.');
        }

        if ($license->isExpired()) {
            return $this->error('License has expired.');
        }

        $domain = $this->normalizeDomain($domain);

        if (! $domain) {
            return $this->error('Invalid domain format.');
        }

        // Check if already activated on this domain
        $existingActivation = $license->activations()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if ($existingActivation) {
            return $this->success('License is already active on this domain.', $license, $existingActivation);
        }

        // Check if there's an active activation on another domain
        $currentActivation = $license->activeActivation;

        if ($currentActivation) {
            // Need to change domain - check if allowed
            if (! $license->canChangeDomain()) {
                return $this->error('Maximum domain changes reached. Contact support.');
            }
        }

        // Use transaction to prevent race conditions
        return DB::transaction(function () use ($license, $domain, $ipAddress, $currentActivation) {
            // Lock the license row for update
            $license = License::where('id', $license->id)->lockForUpdate()->first();

            if ($currentActivation) {
                // Deactivate current domain
                $currentActivation->deactivate('Domain changed to: '.$domain);
                $license->increment('domain_changes_used');
            }

            // Create new activation
            $activation = $license->activations()->create([
                'domain' => $domain,
                'ip_address' => $ipAddress,
                'is_active' => true,
                'activated_at' => now(),
            ]);

            // Set license as active and set expiry on first activation
            if (! $license->activated_at) {
                $license->update([
                    'status' => LicenseStatus::Active,
                    'activated_at' => now(),
                    'expires_at' => now()->addDays($license->validity_days),
                ]);
            } elseif ($license->status !== LicenseStatus::Active) {
                $license->update(['status' => LicenseStatus::Active]);
            }

            $license->refresh();
            $license->load('activeActivation');

            return $this->success('License activated successfully.', $license, $activation);
        });
    }

    /**
     * Validate a license for a domain.
     *
     * @return array{success: bool, message: string, expires_at?: string, days_remaining?: int}
     */
    public function validate(string $licenseKey, string $domain, string $productSlug): array
    {
        $license = License::with(['product', 'activeActivation'])
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return $this->error('License key not found.');
        }

        if ($license->product->slug !== $productSlug) {
            return $this->error('License is not valid for this product.');
        }

        if ($license->status === LicenseStatus::Revoked) {
            return $this->error('License has been revoked.');
        }

        if ($license->isExpired()) {
            // Update status if not already expired
            if ($license->status !== LicenseStatus::Expired) {
                $license->update(['status' => LicenseStatus::Expired]);
            }

            return $this->error('License has expired.');
        }

        $domain = $this->normalizeDomain($domain);

        if (! $domain) {
            return $this->error('Invalid domain format.');
        }

        $activeActivation = $license->activeActivation;

        if (! $activeActivation) {
            return $this->error('License is not activated.');
        }

        if ($activeActivation->domain !== $domain) {
            return $this->error('License is not active on this domain.');
        }

        return [
            'success' => true,
            'message' => 'License is valid.',
            'expires_at' => $license->expires_at?->toIso8601String(),
            'days_remaining' => $license->daysUntilExpiry(),
        ];
    }

    /**
     * Deactivate a license from its current domain.
     *
     * @return array{success: bool, message: string}
     */
    public function deactivate(string $licenseKey, string $domain, string $productSlug, ?string $reason = null): array
    {
        $license = License::with('product')->where('license_key', $licenseKey)->first();

        if (! $license) {
            return $this->error('License key not found.');
        }

        if ($license->product->slug !== $productSlug) {
            return $this->error('License is not valid for this product.');
        }

        $domain = $this->normalizeDomain($domain);

        if (! $domain) {
            return $this->error('Invalid domain format.');
        }

        $activeActivation = $license->activations()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (! $activeActivation) {
            return $this->error('No active license found on this domain.');
        }

        $activeActivation->deactivate($reason ?? 'Deactivated by user');

        return $this->success('License deactivated successfully.');
    }

    /**
     * Get license status and details.
     *
     * @return array{success: bool, license?: License, message?: string}
     */
    public function status(string $licenseKey): array
    {
        $license = License::with(['product', 'activeActivation'])->where('license_key', $licenseKey)->first();

        if (! $license) {
            return ['success' => false, 'message' => 'License key not found.'];
        }

        // Check and update expired status
        if ($license->isExpired() && $license->status !== LicenseStatus::Expired) {
            $license->update(['status' => LicenseStatus::Expired]);
            $license->refresh();
        }

        return ['success' => true, 'license' => $license];
    }

    /**
     * Normalize domain (remove protocol, www, paths, ports, trailing slashes).
     */
    private function normalizeDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));

        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);

        // Remove www prefix
        $domain = preg_replace('#^www\.#', '', $domain);

        // Remove path (everything after first /)
        $domain = explode('/', $domain)[0];

        // Remove port
        $domain = explode(':', $domain)[0];

        // Remove trailing dots
        $domain = rtrim($domain, '.');

        // Basic validation: must have at least one dot or be localhost
        if ($domain !== 'localhost' && ! str_contains($domain, '.')) {
            return null;
        }

        // Check for valid domain characters
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', $domain)) {
            return null;
        }

        return $domain;
    }

    /**
     * @return array{success: bool, message: string, license?: License, activation?: LicenseActivation}
     */
    private function success(string $message, ?License $license = null, ?LicenseActivation $activation = null): array
    {
        $result = ['success' => true, 'message' => $message];

        if ($license) {
            $result['license'] = $license;
        }

        if ($activation) {
            $result['activation'] = $activation;
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function error(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
