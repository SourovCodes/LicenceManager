<?php

namespace App\Filament\Resources\NaldaCsvUploadRequests\Pages;

use App\Filament\Resources\NaldaCsvUploadRequests\NaldaCsvUploadRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNaldaCsvUploadRequest extends EditRecord
{
    protected static string $resource = NaldaCsvUploadRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
