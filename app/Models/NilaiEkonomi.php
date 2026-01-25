<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

class NilaiEkonomi extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Userstamps;

    protected $table = 'nilai_ekonomi';

    protected $fillable = [
        'nama_kelompok',
        'total_transaction_value',
        'year',
        'month',
        'province_id',
        'regency_id',
        'district_id',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function details()
    {
        return $this->hasMany(NilaiEkonomiDetail::class);
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
