<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JenisProduksi extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
