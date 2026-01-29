<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

class HasilHutanBukanKayu extends Model
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $table = "hasil_hutan_bukan_kayu";

    protected $fillable = [
        "year",
        "month",
        "province_id",
        "regency_id",
        "district_id",
        "pengelola_hutan_id",
        "forest_type",
        "volume_target",
        "status",
        "approved_by_kasi_at",
        "approved_by_cdk_at",
        "rejection_note",
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function province()
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function regency()
    {
        return $this->belongsTo(Regencies::class, 'regency_id');
    }

    public function district()
    {
        return $this->belongsTo(Districts::class, 'district_id');
    }

    public function pengelolaHutan()
    {
        return $this->belongsTo(PengelolaHutan::class, 'pengelola_hutan_id');
    }

    public function details()
    {
        return $this->hasMany(HasilHutanBukanKayuDetail::class, 'hasil_hutan_bukan_kayu_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
