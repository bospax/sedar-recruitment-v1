<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualEvaluation extends Model
{
    use HasFactory;

    protected $table = 'annual_evaluations';

    protected $casts = [
        'measures' => 'array',
        'performance_discussion' => 'array',
    ];
}
