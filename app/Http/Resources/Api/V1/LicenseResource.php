<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\License
 */
class LicenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'license_key' => $this->license_key,
            'status' => $this->status->value,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->daysUntilExpiry(),
            'domain' => $this->activeActivation?->domain,
        ];
    }
}
