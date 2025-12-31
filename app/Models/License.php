<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class License extends Model
{
    /** @use HasFactory<\Database\Factories\LicenseFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'license_key',
        'customer_name',
        'customer_email',
        'status',
        'validity_days',
        'activated_at',
        'expires_at',
        'max_domain_changes',
        'domain_changes_used',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'validity_days' => 'integer',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_domain_changes' => 'integer',
            'domain_changes_used' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function activeActivation(): HasOne
    {
        return $this->hasOne(LicenseActivation::class)->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active;
    }

    public function isExpired(): bool
    {
        return $this->status === LicenseStatus::Expired
            || ($this->expires_at && $this->expires_at->isPast());
    }

    public function canChangeDomain(): bool
    {
        return $this->domain_changes_used < $this->max_domain_changes;
    }

    public function remainingDomainChanges(): int
    {
        return max(0, $this->max_domain_changes - $this->domain_changes_used);
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public static function generateLicenseKey(): string
    {
        return strtoupper(implode('-', [
            Str::random(4),
            Str::random(4),
            Str::random(4),
            Str::random(4),
        ]));
    }

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            if (empty($license->license_key)) {
                $license->license_key = static::generateLicenseKey();
            }
        });
    }
}
