<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    /**
     * Activate a license on a domain.
     *
     * @return array{message: string, license: License, activation: LicenseActivation}
     */
    public function activate(string $licenseKey, string $domain, string $productSlug, ?string $ipAddress = null): array
    {
        $license = License::with(['product', 'activeActivation'])
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            $this->abort('License key not found.');
        }

        if ($license->product->slug !== $productSlug) {
            $this->abort('License is not valid for this product.');
        }

        if ($license->status === LicenseStatus::Revoked) {
            $this->abort('License has been revoked.');
        }

        if ($license->isExpired()) {
            $this->abort('License has expired.');
        }

        $domain = $this->normalizeDomain($domain);

        if (! $domain) {
            $this->abort('Invalid domain format.');
        }

        // Check if already activated on this domain
        $existingActivation = $license->activations()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if ($existingActivation) {
            return [
                'message' => 'License is already active on this domain.',
                'license' => $license,
                'activation' => $existingActivation,
            ];
        }

        // Check if there's an active activation on another domain
        $currentActivation = $license->activeActivation;

        if ($currentActivation) {
            // Need to change domain - check if allowed
            if (! $license->canChangeDomain()) {
                $this->abort('Maximum domain changes reached. Contact support.');
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

            return [
                'message' => 'License activated successfully.',
                'license' => $license,
                'activation' => $activation,
            ];
        });
    }

    /**
     * Validate a license for a domain.
     *
     * @return array{message: string, expires_at: string, days_remaining: int}
     */
    public function validate(string $licenseKey, string $domain, string $productSlug): array
    {
        $license = License::with(['product', 'activeActivation'])
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            $this->abort('License key not found.');
        }

        if ($license->product->slug !== $productSlug) {
            $this->abort('License is not valid for this product.');
        }

        if ($license->status === LicenseStatus::Revoked) {
            $this->abort('License has been revoked.');
        }

        if ($license->isExpired()) {
            // Update status if not already expired
            if ($license->status !== LicenseStatus::Expired) {
                $license->update(['status' => LicenseStatus::Expired]);
            }

            $this->abort('License has expired.');
        }

        $domain = $this->normalizeDomain($domain);

        if (! $domain) {
            $this->abort('Invalid domain format.');
        }

        $activeActivation = $license->activeActivation;

        if (! $activeActivation) {
            $this->abort('License is not activated.');
        }

        if ($activeActivation->domain !== $domain) {
            $this->abort('License is not active on this domain.');
        }

        return [
            'message' => 'License is valid.',
            'expires_at' => $license->expires_at?->toIso8601String(),
            'days_remaining' => $license->daysUntilExpiry(),
        ];
    }

    /**
     * Deactivate a license from its current domain.
     *
     * @return array{message: string}
     */
    public function deactivate(string $licenseKey, string $domain, string $productSlug, ?string $reason = null): array
    {
        $license = License::with('product')->where('license_key', $licenseKey)->first();

        if (! $license) {
            $this->abort('License key not found.');
        }

        if ($license->product->slug !== $productSlug) {
            $this->abort('License is not valid for this product.');
        }

        $domain = $this->normalizeDomain($domain);

        if (! $domain) {
            $this->abort('Invalid domain format.');
        }

        $activeActivation = $license->activations()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->first();

        if (! $activeActivation) {
            $this->abort('No active license found on this domain.');
        }

        $activeActivation->deactivate($reason ?? 'Deactivated by user');

        return ['message' => 'License deactivated successfully.'];
    }

    /**
     * Get license status and details.
     *
     * @return License
     */
    public function status(string $licenseKey): License
    {
        $license = License::with(['product', 'activeActivation'])->where('license_key', $licenseKey)->first();

        if (! $license) {
            $this->abort('License key not found.', 404);
        }

        // Check and update expired status
        if ($license->isExpired() && $license->status !== LicenseStatus::Expired) {
            $license->update(['status' => LicenseStatus::Expired]);
            $license->refresh();
        }

        return $license;
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
     * Abort with a JSON error response.
     *
     * @throws HttpResponseException
     */
    private function abort(string $message, int $status = 422): never
    {
        throw new HttpResponseException(
            response()->json(['message' => $message], $status)
        );
    }
}
