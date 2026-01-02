<?php

namespace App\Filament\Resources\NaldaCsvUploadRequests\Pages;

use App\Filament\Resources\NaldaCsvUploadRequests\NaldaCsvUploadRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNaldaCsvUploadRequests extends ListRecords
{
    protected static string $resource = NaldaCsvUploadRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
