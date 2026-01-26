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

    public function pbphh()
    {
        return $this->belongsToMany(Pbphh::class, 'pbphh_jenis_produksi', 'jenis_produksi_id', 'pbphh_id')
            ->withPivot('kapasitas_ijin')
            ->withTimestamps();
    }
}
