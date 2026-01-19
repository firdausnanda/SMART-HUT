<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisProduksi extends Model
{
    use HasFactory;

    protected $table = "m_jenis_produksi";

    protected $fillable = [
        'name',
    ];
}
