<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyEvaluation extends Model
{
    use HasFactory;

    protected $table = 'monthly_evaluations';

    protected $casts = [
        'measures' => 'array',
        'development_plan' => 'array',
        'development_area' => 'array'
    ];
}
