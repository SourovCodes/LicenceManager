<?php

namespace App\Filament\Resources\Licenses\Schemas;

use App\Enums\LicenseStatus;
use App\Models\License;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License Details')
                    ->description('Core license information and key.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->columnSpan(1),
                        TextInput::make('license_key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => License::generateLicenseKey())
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Auto-generated if left empty.')
                            ->suffixAction(
                                Action::make('regenerate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(fn (callable $set) => $set('license_key', License::generateLicenseKey()))
                            ),
                        Select::make('status')
                            ->options(LicenseStatus::class)
                            ->default(LicenseStatus::Active)
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Customer Information')
                    ->description('Customer details associated with this license.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('customer_name')
                            ->maxLength(255)
                            ->placeholder('John Doe'),
                        TextInput::make('customer_email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('customer@example.com'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Validity & Activation')
                    ->description('Configure license duration and activation settings.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextInput::make('validity_days')
                            ->label('Validity Period (Days)')
                            ->required()
                            ->numeric()
                            ->default(365)
                            ->minValue(1)
                            ->suffix('days')
                            ->helperText('Duration from activation date.'),
                        DateTimePicker::make('activated_at')
                            ->label('Activated At')
                            ->native(false)
                            ->displayFormat('M d, Y H:i')
                            ->helperText('Set when the license is first used.'),
                        DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->native(false)
                            ->displayFormat('M d, Y H:i')
                            ->helperText('Auto-calculated from activation + validity.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Domain Management')
                    ->description('Control domain change limits.')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextInput::make('max_domain_changes')
                            ->label('Max Domain Changes')
                            ->required()
                            ->numeric()
                            ->default(3)
                            ->minValue(0)
                            ->helperText('Maximum allowed domain changes.'),
                        TextInput::make('domain_changes_used')
                            ->label('Domain Changes Used')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('How many times domain has been changed.'),
                        Placeholder::make('remaining_changes')
                            ->label('Remaining Changes')
                            ->content(function ($get) {
                                $max = (int) $get('max_domain_changes');
                                $used = (int) $get('domain_changes_used');

                                return max(0, $max - $used);
                            })
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('notes')
                            ->label('')
                            ->rows(3)
                            ->placeholder('Internal notes about this license...'),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),
            ]);
    }
}
