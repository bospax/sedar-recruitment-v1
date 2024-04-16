<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KPI extends Model
{
    use HasFactory;
    
    protected $table = 'kpis';

    protected $casts = [
        'measures' => 'array'
    ];
}
