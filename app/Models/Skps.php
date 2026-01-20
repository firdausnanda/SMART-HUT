<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

class Skps extends Model
{
    use HasFactory, Userstamps, SoftDeletes, LogsActivity;

    protected $fillable = [
        "province_id",
        "regency_id",
        "district_id",
        "id_skema_perhutanan_sosial",
        "potential",
        "ps_area",
        "number_of_kk",
        "status",
        "approved_by_kasi_at",
        "approved_by_cdk_at",
        "rejection_note",
        "created_by",
        "updated_by",
        "deleted_by",
    ];

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

    public function skema()
    {
        return $this->belongsTo(SkemaPerhutananSosial::class, 'id_skema_perhutanan_sosial');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
