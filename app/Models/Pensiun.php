<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pensiun extends Model
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $table = 'pensiuns';

    protected $fillable = [
        'pegawai_id',
        'jenis_pensiun',
        'tmt_pensiun',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tmt_pensiun' => 'date',
        'approved_by_kasi_at' => 'datetime',
        'approved_by_cdk_at' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
