<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ulasan extends Model
{
    use HasFactory;

    protected $table = 'ulasan';

    protected $fillable = [
        'nama_wisata',
        'reviewer',
        'rating',
        'ulasan',
        'tanggal',
        'scraping_date',
        'is_processed'
    ];
}
