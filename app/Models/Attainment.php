<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attainment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attainment_name'
    ];
}
