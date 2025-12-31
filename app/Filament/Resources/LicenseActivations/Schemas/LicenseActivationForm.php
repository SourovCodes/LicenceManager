<?php

namespace App\Filament\Resources\LicenseActivations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenseActivationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activation Details')
                    ->description('Link this activation to a license and specify the domain.')
                    ->icon('heroicon-o-computer-desktop')
                    ->columns(2)
                    ->schema([
                        Select::make('license_id')
                            ->label('License')
                            ->relationship('license', 'license_key')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Select the license key to activate.'),
                        TextInput::make('domain')
                            ->label('Domain')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('example.com')
                            ->helperText('The domain where this license is activated.'),
                        TextInput::make('ip_address')
                            ->label('IP Address')
                            ->maxLength(45)
                            ->placeholder('192.168.1.1')
                            ->helperText('The IP address of the server (optional).'),
                    ]),

                Section::make('Activation Status')
                    ->description('Manage the activation state and local key.')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        TextInput::make('local_key')
                            ->label('Local Key')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-generated unique key for this activation.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Toggle to activate or deactivate this domain.'),
                        DateTimePicker::make('activated_at')
                            ->label('Activated At')
                            ->default(now())
                            ->helperText('When this activation was created.'),
                        DateTimePicker::make('deactivated_at')
                            ->label('Deactivated At')
                            ->helperText('When this activation was deactivated (if applicable).'),
                    ]),

                Section::make('Deactivation Reason')
                    ->description('Provide a reason if deactivating this license activation.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->collapsed()
                    ->schema([
                        Textarea::make('deactivation_reason')
                            ->label('Reason for Deactivation')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Enter reason for deactivation...')
                            ->helperText('Document why this activation was deactivated.'),
                    ]),
            ]);
    }
}
