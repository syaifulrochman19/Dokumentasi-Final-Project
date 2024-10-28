<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaAirPermeter extends Model
{
    use HasFactory;

    protected $fillable = [
        'harga',
        'beban_meteran'
    ];
}
