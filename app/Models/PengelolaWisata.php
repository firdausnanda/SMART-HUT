<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengelolaWisata extends Model
{
    use HasFactory;

    protected $fillable = [
        "name"
    ];

    protected $table = "m_pengelola_wisata";
}
