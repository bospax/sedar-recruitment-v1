<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobBand extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'jobbands';

    protected $fillable = [
        'jobband_name',
        'order'
    ];
}
