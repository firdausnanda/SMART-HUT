<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BangunanKta extends Model
{
  use HasFactory, LogsActivity;

  protected $table = 'm_bangunan_kta';

  protected $fillable = [
    'name',
    'description',
  ];

  public function details()
  {
    return $this->hasMany(RhlTeknisDetail::class, 'bangunan_kta_id');
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logAll()
      ->logOnlyDirty();
  }
}
