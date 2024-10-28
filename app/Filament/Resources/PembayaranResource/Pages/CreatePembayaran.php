<?php

namespace App\Filament\Resources\PembayaranResource\Pages;

use App\Filament\Resources\PembayaranResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Pembayaran;

class CreatePembayaran extends CreateRecord
{
    protected static string $resource = PembayaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $validator = Validator::make($data, [
    //         'penduduk_id' => 'required|exists:penduduks,id',
    //         'kategori_id' => 'required|exists:kategoris,id',
    //         'jumlah_pengeluaran' => 'required|numeric',
    //         'tanggal_pengeluaran' => 'required|date',
    //         'deskripsi' => 'required|string|max:255',
    //         'bukti_pengeluaran' => 'nullable|image',
    //     ]);

    //     $validator->after(function ($validator) use ($data) {
    //         $exists = Pembayaran::where('penduduk_id', $data['penduduk_id'])
    //             ->where('tanggal_pengeluaran', $data['tanggal_pengeluaran'])
    //             ->where('jumlah_pengeluaran', $data['jumlah_pengeluaran'])
    //             ->exists();

    //         if ($exists) {
    //             $validator->errors()->add('penduduk_id', 'Data pembayaran ini sudah ada.');
    //         }
    //     });

    //     $validator->validate();

    //     return $data;
    // }

}
