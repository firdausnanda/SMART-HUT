<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

class HasilHutanKayu extends Model
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $fillable = [
        "year",
        'year',
        'month',
        'province_id',
        'regency_id',
        'district_id',
        'pengelola_hutan_id',
        'pengelola_wisata_id',
        'forest_type',
        'volume_target',
        'status',
        'rejected_note',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'created_by',
        'updated_by',
    ];

    protected $table = 'hasil_hutan_kayu';

    public function province()
    {
        return $this->belongsTo(\App\Models\Provinces::class, 'province_id');
    }

    public function regency()
    {
        return $this->belongsTo(\App\Models\Regencies::class, 'regency_id');
    }

    public function district()
    {
        return $this->belongsTo(\App\Models\Districts::class, 'district_id');
    }

    public function pengelolaHutan()
    {
        return $this->belongsTo(PengelolaHutan::class, 'pengelola_hutan_id');
    }

    public function pengelolaWisata()
    {
        return $this->belongsTo(PengelolaWisata::class, 'pengelola_wisata_id');
    }

    public function details()
    {
        return $this->hasMany(HasilHutanKayuDetail::class, 'hasil_hutan_kayu_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
