<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeAnalisis extends Model
{
    protected $table = 'periode_analisis';

    protected $fillable = [
        'nama',
        'bulan',
        'tahun'
    ];
}