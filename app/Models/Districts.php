<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Districts extends Model
{
    use HasFactory, LogsActivity;

    protected $table = "m_districts";

    protected $fillable = [
        "id",
        "regency_id",
        "name",
    ];

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'id' => 'string',
    ];

    public function regency()
    {
        return $this->belongsTo(Regencies::class, "regency_id");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
