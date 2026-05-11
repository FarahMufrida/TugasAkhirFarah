<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ulasan extends Model
{
    use HasFactory;

    protected $table = 'ulasan';

    protected $fillable = [
        'wisata',
        'reviewer',
        'rating',
        'ulasan',
        'tanggal',
        'scraping_date',
        'periode_id',
        'sentimen',
        'is_processed'
    ];
}
