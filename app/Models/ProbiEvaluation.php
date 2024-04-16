<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProbiEvaluation extends Model
{
    use HasFactory;

    protected $table = 'probi_evaluations';

    protected $casts = [
        'measures' => 'array'
    ];
}
