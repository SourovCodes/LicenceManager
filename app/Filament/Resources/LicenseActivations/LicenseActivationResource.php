<?php

namespace App\Filament\Resources\LicenseActivations;

use App\Filament\Resources\LicenseActivations\Pages\CreateLicenseActivation;
use App\Filament\Resources\LicenseActivations\Pages\EditLicenseActivation;
use App\Filament\Resources\LicenseActivations\Pages\ListLicenseActivations;
use App\Filament\Resources\LicenseActivations\Schemas\LicenseActivationForm;
use App\Filament\Resources\LicenseActivations\Tables\LicenseActivationsTable;
use App\Models\LicenseActivation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LicenseActivationResource extends Resource
{
    protected static ?string $model = LicenseActivation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Activations';

    protected static UnitEnum|string|null $navigationGroup = 'License Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return LicenseActivationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenseActivationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenseActivations::route('/'),
            'create' => CreateLicenseActivation::route('/create'),
            'edit' => EditLicenseActivation::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['domain', 'ip_address', 'local_key', 'license.license_key', 'license.customer_name'];
    }
}
