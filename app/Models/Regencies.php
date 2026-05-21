<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Regencies extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        "id",
        "province_id",
        "name"
    ];

    protected $table = "m_regencies";

    public $incrementing = false;

    public $timestamps = false;

    public function province()
    {
        return $this->belongsTo(Provinces::class, "province_id");
    }

    public function cdks()
    {
        return $this->belongsToMany(Cdk::class, 'cdk_regency', 'regency_id', 'cdk_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
