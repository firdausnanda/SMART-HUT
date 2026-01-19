<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kayu extends Model
{
    use HasFactory;

    protected $table = 'm_kayu';
    protected $fillable = [
        'name',
    ];
}
