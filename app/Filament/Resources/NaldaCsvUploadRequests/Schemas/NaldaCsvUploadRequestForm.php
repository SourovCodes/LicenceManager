<?php

namespace App\Filament\Resources\NaldaCsvUploadRequests\Schemas;

use App\Enums\NaldaCsvUploadStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NaldaCsvUploadRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License & Source')
                    ->description('License and domain information.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Select::make('license_id')
                            ->relationship('license', 'license_key')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('example.com'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('SFTP Credentials')
                    ->description('SFTP server connection details.')
                    ->icon('heroicon-o-server')
                    ->schema([
                        TextInput::make('sftp_host')
                            ->label('Host')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('sftp.example.com'),
                        TextInput::make('sftp_port')
                            ->label('Port')
                            ->required()
                            ->numeric()
                            ->default(22)
                            ->minValue(1)
                            ->maxValue(65535),
                        TextInput::make('sftp_username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sftp_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('CSV File')
                    ->description('Upload the CSV file.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('csv')
                            ->collection('csv')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Status')
                    ->description('Processing status and results.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('status')
                            ->options(NaldaCsvUploadStatus::class)
                            ->default(NaldaCsvUploadStatus::Pending)
                            ->required()
                            ->native(false),
                        DateTimePicker::make('processed_at')
                            ->label('Processed At'),
                        Textarea::make('error_message')
                            ->label('Error Message')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
