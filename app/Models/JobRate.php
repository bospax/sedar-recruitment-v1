<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRate extends Model
{
    use HasFactory;

    protected $table = 'jobrates';

    protected $fillable = [
        'jobrate_name'
    ];
}
