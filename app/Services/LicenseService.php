<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseActivation;

class LicenseService
{
    /**
     * Activate a license on a domain.
     *
     * @return array{success: bool, message: string, license?: License, activation?: LicenseActivation}
     */
    public function activate(string $licenseKey, string $domain, string $productSlug, ?string $ipAddress = null): array
    {
        $license = License::with('product')->where('license_key', $licenseKey)->first();

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

        return $this->success('License activated successfully.', $license, $activation);
    }

    /**
     * Validate a license for a domain.
     *
     * @return array{valid: bool, message: string, license?: License}
     */
    public function validate(string $licenseKey, string $domain, string $productSlug): array
    {
        $license = License::with('product')->where('license_key', $licenseKey)->first();

        if (! $license) {
            return ['valid' => false, 'message' => 'License key not found.'];
        }

        if ($license->product->slug !== $productSlug) {
            return ['valid' => false, 'message' => 'License is not valid for this product.'];
        }

        if ($license->status === LicenseStatus::Revoked) {
            return ['valid' => false, 'message' => 'License has been revoked.', 'license' => $license];
        }

        if ($license->isExpired()) {
            // Update status if not already expired
            if ($license->status !== LicenseStatus::Expired) {
                $license->update(['status' => LicenseStatus::Expired]);
            }

            return ['valid' => false, 'message' => 'License has expired.', 'license' => $license];
        }

        $domain = $this->normalizeDomain($domain);

        $activeActivation = $license->activeActivation;

        if (! $activeActivation) {
            return ['valid' => false, 'message' => 'License is not activated.', 'license' => $license];
        }

        if ($activeActivation->domain !== $domain) {
            return ['valid' => false, 'message' => 'License is not active on this domain.', 'license' => $license];
        }

        return [
            'valid' => true,
            'message' => 'License is valid.',
            'license' => $license,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'days_remaining' => $license->daysUntilExpiry(),
        ];
    }

    /**
     * Deactivate a license from its current domain.
     *
     * @return array{success: bool, message: string, license?: License}
     */
    public function deactivate(string $licenseKey, string $domain, ?string $reason = null): array
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (! $license) {
            return $this->error('License key not found.');
        }

        $domain = $this->normalizeDomain($domain);

        $activeActivation = $license->activations()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (! $activeActivation) {
            return $this->error('No active license found on this domain.');
        }

        $activeActivation->deactivate($reason ?? 'Deactivated by user');

        return $this->success('License deactivated successfully.', $license->refresh());
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
     * Normalize domain (remove protocol, www, trailing slashes).
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain;
    }

    /**
     * @return array{success: bool, message: string, license?: License, activation?: LicenseActivation}
     */
    private function success(string $message, License $license, ?LicenseActivation $activation = null): array
    {
        $result = ['success' => true, 'message' => $message, 'license' => $license];

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
