<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Villages extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        "id",
        "district_id",
        "name"
    ];

    public $incrementing = false;

    protected $table = "m_villages";

    public $timestamps = false;

    public function district()
    {
        return $this->belongsTo(Districts::class, "district_id");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
