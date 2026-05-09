<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilAnalisis extends Model
{
    protected $table = 'hasil_analisis';

    protected $fillable = [
        'wisata',
        'ulasan_asli',        // ← fix dari ulasan_terolah
        'ulasan_bersih',      // ← tambah ini
        'hasil_preprocessing',
        'sentimen',
        'probabilitas'
    ];
}