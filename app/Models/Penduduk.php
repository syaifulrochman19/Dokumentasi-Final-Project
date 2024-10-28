<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penduduk extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_penduduk',
        'alamat',
        'no_telepon',
    ];
    protected $casts = [
        'no_telepon' => 'string',
    ];


}
