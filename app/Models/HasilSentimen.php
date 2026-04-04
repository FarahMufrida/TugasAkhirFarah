<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilSentimen extends Model
{
    use HasFactory;

    protected $table = 'hasil_sentimen';

    protected $fillable = [
        'wisata',
        'ulasan',
        'sentimen'
    ];
}
