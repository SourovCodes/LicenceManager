<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\License
 */
class LicenseStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'license_key' => $this->license_key,
            'status' => $this->status->value,
            'product' => [
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'type' => $this->product->type->value,
            ],
            'customer_name' => $this->customer_name,
            'activation' => $this->whenLoaded('activeActivation', fn () => [
                'domain' => $this->activeActivation->domain,
                'activated_at' => $this->activeActivation->activated_at?->toIso8601String(),
            ]),
            'activated_at' => $this->activated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->daysUntilExpiry(),
            'domain_changes' => [
                'used' => $this->domain_changes_used,
                'max' => $this->max_domain_changes,
                'remaining' => $this->remainingDomainChanges(),
            ],
        ];
    }
}
