<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DivisionCategory extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'division_categories';

    protected $fillable = [
        'category_name'
    ];
}
