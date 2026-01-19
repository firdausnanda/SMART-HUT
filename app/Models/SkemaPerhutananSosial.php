<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkemaPerhutananSosial extends Model
{
    use HasFactory;

    protected $table = 'm_skema_perhutanan_sosial';

    protected $fillable = [
        'name',
    ];
}
