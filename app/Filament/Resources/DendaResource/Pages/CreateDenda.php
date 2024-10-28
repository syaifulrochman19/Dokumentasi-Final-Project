<?php

namespace App\Filament\Resources\DendaResource\Pages;

use App\Filament\Resources\DendaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Validator;
use App\Models\Denda;

class CreateDenda extends CreateRecord
{
    protected static string $resource = DendaResource::class;


    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $validator = Validator::make($data, [
    //         'penduduk_id' => 'required|exists:penduduks,id',
    //         'kategori_id' => 'required|exists:kategoris,id',
    //         'jumlah_denda' => 'required|numeric',
    //         'tanggal_denda' => 'required|date',
    //         'keterangan' => 'required|string|max:255',
    //         'bukti_denda' => 'nullable|image',
    //     ]);

    //     $validator->after(function ($validator) use ($data) {
    //         $exists = Denda::where('penduduk_id', $data['penduduk_id'])
    //             ->where('tanggal_denda', $data['tanggal_denda'])
    //             ->where('jumlah_denda', $data['jumlah_denda'])
    //             ->exists();

    //         if ($exists) {
    //             $validator->errors()->add('penduduk_id', 'Data denda ini sudah ada.');
    //         }
    //     });

    //     $validator->validate();

    //     return $data;
    // }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
