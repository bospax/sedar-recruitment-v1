<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Honorary extends Model
{
    use HasFactory;

    protected $fillable = [
        'honorary_name'
    ];
}
