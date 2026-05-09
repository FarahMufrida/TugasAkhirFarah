<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluasiModel extends Model
{
    protected $table = 'evaluasi_model';

    protected $fillable = [
        'precision',
        'recall',
        'f1_score',
        'accuracy',
        'tp',
        'tn',
        'fp',
        'fn'
    ];
}