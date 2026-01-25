<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NilaiEkonomiDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'nilai_ekonomi_id',
        'commodity_id',
        'production_volume',
        'satuan',
        'transaction_value',
    ];

    public function commodity()
    {
        return $this->belongsTo(Commodity::class);
    }
}
