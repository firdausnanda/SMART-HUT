<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Penanaman extends Model
{
    use LogsActivity;

    protected $table = 'penanaman';

    protected $fillable = [
        'year', 'month', 'regency_id', 'district_id', 'village_id',
        'location_name', 'coordinates', 'realization_ha', 'fund_source', 'status',
        'submitted_at', 'submitted_by', 'created_by', 'updated_by',
    ];

    protected $casts = ['submitted_at' => 'datetime', 'realization_ha' => 'decimal:2'];

    public function species() { return $this->hasMany(PenanamanSpecies::class); }
    public function events() { return $this->hasMany(PenanamanEvent::class)->orderBy('created_at'); }
    public function regency() { return $this->belongsTo(Regencies::class, 'regency_id'); }
    public function district() { return $this->belongsTo(Districts::class, 'district_id'); }
    public function village() { return $this->belongsTo(Villages::class, 'village_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
