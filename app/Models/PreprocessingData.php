<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreprocessingData extends Model
{
    use HasFactory;

    protected $table = 'preprocessing_data';

    protected $fillable = [
        'ulasan_asli',
        'cleaning',
        'tokenizing',
    ];
}
