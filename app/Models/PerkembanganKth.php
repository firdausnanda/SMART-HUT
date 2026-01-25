<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PerkembanganKth extends Model
{
  use HasFactory, SoftDeletes, Userstamps, LogsActivity;

  protected $table = 'perkembangan_kth';

  protected $fillable = [
    'year',
    'month',
    'province_id',
    'regency_id',
    'district_id',
    'village_id',
    'nama_kth',
    'nomor_register',
    'kelas_kelembagaan',
    'jumlah_anggota',
    'luas_kelola',
    'potensi_kawasan',
    'status',
    'approved_by_kasi_at',
    'approved_by_cdk_at',
    'rejection_note',
    'created_by',
    'updated_by',
    'deleted_by',
  ];

  protected $casts = [
    'approved_by_kasi_at' => 'datetime',
    'approved_by_cdk_at' => 'datetime',
  ];

  public function province_rel()
  {
    return $this->belongsTo(Provinces::class, 'province_id');
  }

  public function regency_rel()
  {
    return $this->belongsTo(Regencies::class, 'regency_id');
  }

  public function district_rel()
  {
    return $this->belongsTo(Districts::class, 'district_id');
  }

  public function village_rel()
  {
    return $this->belongsTo(Villages::class, 'village_id');
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
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

  public function getKelasLabelAttribute()
  {
    return match ($this->kelas_kelembagaan) {
      'pemula' => 'Pemula',
      'madya' => 'Madya',
      'utama' => 'Utama',
      default => ucfirst($this->kelas_kelembagaan),
    };
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logAll()
      ->logOnlyDirty();
  }
}
