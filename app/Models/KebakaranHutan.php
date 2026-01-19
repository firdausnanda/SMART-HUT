<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;

class KebakaranHutan extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        'year',
        'month',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'coordinates',
        'id_pengelola_wisata',
        'area_function',
        'number_of_fires',
        'fire_area',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $table = 'kebakaran_hutan';

    public function pengelolaWisata()
    {
        return $this->belongsTo(PengelolaWisata::class, 'id_pengelola_wisata');
    }

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

    public function village()
    {
        return $this->belongsTo(Villages::class);
    }
}
