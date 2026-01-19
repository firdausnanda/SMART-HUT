<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;

class IndustriBerizin extends Model
{
    use HasFactory, Userstamps, SoftDeletes;

    protected $fillable = [
        'year',
        'month',
        'province_id',
        'regency_id',
        'district_id',
        'phhk_pbhh',
        'phhbk_pbphh',
        'id_jenis_produksi',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
    ];

    protected $table = 'industri_berizin';

    public function province()
    {
        return $this->belongsTo(Provinces::class);
    }

    public function regency()
    {
        return $this->belongsTo(Regencies::class);
    }

    public function district()
    {
        return $this->belongsTo(Districts::class);
    }

    public function jenis_produksi()
    {
        return $this->belongsTo(JenisProduksi::class, 'id_jenis_produksi');
    }
}
