<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilAnalisis extends Model
{
    use HasFactory;

    protected $table = 'hasil_analisis';

    protected $fillable = [
        'wisata',
        'ulasan_terolah',
        'sentimen',
        'probabilitas'
    ];
}
