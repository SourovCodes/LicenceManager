<?php

namespace App\Filament\Resources\NaldaCsvUploadRequests;

use App\Filament\Resources\NaldaCsvUploadRequests\Pages\CreateNaldaCsvUploadRequest;
use App\Filament\Resources\NaldaCsvUploadRequests\Pages\EditNaldaCsvUploadRequest;
use App\Filament\Resources\NaldaCsvUploadRequests\Pages\ListNaldaCsvUploadRequests;
use App\Filament\Resources\NaldaCsvUploadRequests\Schemas\NaldaCsvUploadRequestForm;
use App\Filament\Resources\NaldaCsvUploadRequests\Tables\NaldaCsvUploadRequestsTable;
use App\Models\NaldaCsvUploadRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NaldaCsvUploadRequestResource extends Resource
{
    protected static ?string $model = NaldaCsvUploadRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|UnitEnum|null $navigationGroup = 'Nalda';

    protected static ?string $modelLabel = 'CSV Upload Request';

    protected static ?string $pluralModelLabel = 'CSV Upload Requests';

    public static function form(Schema $schema): Schema
    {
        return NaldaCsvUploadRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NaldaCsvUploadRequestsTable::configure($table);
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
            'index' => ListNaldaCsvUploadRequests::route('/'),
            'create' => CreateNaldaCsvUploadRequest::route('/create'),
            'edit' => EditNaldaCsvUploadRequest::route('/{record}/edit'),
        ];
    }
}
