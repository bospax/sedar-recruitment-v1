<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaEvaluation extends Model
{
    use HasFactory;

    protected $table = 'da_evaluations';

    protected $casts = [
        'measures' => 'array',
        'prev_measures' => 'array',
        'performance_discussion' => 'array',
    ];
}
