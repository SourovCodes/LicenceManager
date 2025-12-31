<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    /** @use HasFactory<\Database\Factories\LicenseActivationFactory> */
    use HasFactory;

    protected $fillable = [
        'license_id',
        'domain',
        'ip_address',
        'is_active',
        'activated_at',
        'deactivated_at',
        'deactivation_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function deactivate(?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (LicenseActivation $activation) {
            if (empty($activation->activated_at)) {
                $activation->activated_at = now();
            }
        });
    }
}
