<?php

namespace App\Filament\Resources\TagihanResource\Pages;

use App\Filament\Resources\TagihanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateTagihan extends CreateRecord
{
    protected static string $resource = TagihanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Validator::make($data, [
            'penduduk_id' => 'required|exists:penduduks,id',
            'bulan_tagihan' => [
                'required',
                Rule::unique('tagihans')
                    ->where(function ($query) use ($data) {
                        return $query->where('penduduk_id', $data['penduduk_id'])
                                     ->where('bulan_tagihan', $data['bulan_tagihan'])
                                     ->where('tahun_tagihan', $data['tahun_tagihan']);
                    }),
                function ($attribute, $value, $fail) {
                    if (!in_array($value, [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ])) {
                        $fail('Pastikan bulan yang diinputkan benar.');
                    }
                },
            ],
            'tahun_tagihan' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value < 2024 || $value > 2070) {
                        $fail('Pastikan tahun yang diinputkan benar.');
                    }
                },
            ],
            'meteran_awal' => 'required|numeric',
            'meteran_akhir' => 'required|numeric|gt:meteran_awal',
            'harga_air_permeter_id' => 'required|exists:harga_air_permeters,id',
            'total_tagihan' => 'required|numeric',
            'status_tagihan' => 'required|in:Lunas,Belum Lunas',
        ])->validate();

        return $data;
    }
}
