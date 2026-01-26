<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

class RhlTeknis extends Model
{
  use HasFactory, SoftDeletes, Userstamps, LogsActivity;

  protected $table = 'rhl_teknis';

  protected $fillable = [
    'year',
    'month',
    'target_annual',
    'fund_source',
    'province_id',
    'regency_id',
    'district_id',
    'village_id',
    'coordinates',
    'status',
    'approved_by_kasi_at',
    'approved_by_cdk_at',
    'rejection_note',
  ];

  public function details()
  {
    return $this->hasMany(RhlTeknisDetail::class, 'rhl_teknis_id');
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function regency()
  {
    return $this->belongsTo(Regencies::class, 'regency_id');
  }

  public function district()
  {
    return $this->belongsTo(Districts::class, 'district_id');
  }

  public function village()
  {
    return $this->belongsTo(Villages::class, 'village_id');
  }

  public function getStatusColorAttribute()
  {
    return match ($this->status) {
      'draft' => 'gray',
      'waiting_kasi' => 'yellow',
      'waiting_cdk' => 'blue',
      'final' => 'green',
      'rejected' => 'red',
      default => 'gray',
    };
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logAll()
      ->logOnlyDirty();
  }
}
