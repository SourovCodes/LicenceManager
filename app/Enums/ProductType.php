<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasLabel, HasColor, HasIcon
{
    case Plugin = 'plugin';
    case Theme = 'theme';
    case SourceCode = 'source_code';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Plugin => 'Plugin',
            self::Theme => 'Theme',
            self::SourceCode => 'Source Code',
            self::Other => 'Other',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Plugin => 'info',
            self::Theme => 'warning',
            self::SourceCode => 'success',
            self::Other => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Plugin => 'heroicon-o-puzzle-piece',
            self::Theme => 'heroicon-o-paint-brush',
            self::SourceCode => 'heroicon-o-code-bracket',
            self::Other => 'heroicon-o-question-mark-circle',
        };
    }
}
