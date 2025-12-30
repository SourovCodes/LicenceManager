<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LicenseStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'warning',
            self::Revoked => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::Expired => 'heroicon-o-clock',
            self::Revoked => 'heroicon-o-x-circle',
        };
    }
}
