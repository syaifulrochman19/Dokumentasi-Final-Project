<?php

namespace App\Filament\Resources\HargaAirPermeterResource\Pages;

use App\Filament\Resources\HargaAirPermeterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHargaAirPermeter extends CreateRecord
{
    protected static string $resource = HargaAirPermeterResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
