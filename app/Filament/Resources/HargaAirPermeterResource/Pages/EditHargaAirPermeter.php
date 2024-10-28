<?php

namespace App\Filament\Resources\HargaAirPermeterResource\Pages;

use App\Filament\Resources\HargaAirPermeterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHargaAirPermeter extends EditRecord
{
    protected static string $resource = HargaAirPermeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
