<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PenanamanTarget extends Model
{
    use LogsActivity;

    protected $table = 'penanaman_targets';
    protected $fillable = ['regency_id', 'year', 'target_ha', 'target_batang', 'updated_by'];
    protected $casts = ['target_ha' => 'decimal:2'];
    public function regency() { return $this->belongsTo(Regencies::class, 'regency_id'); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
