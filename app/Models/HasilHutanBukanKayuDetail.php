<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilHutanBukanKayuDetail extends Model
{
  use HasFactory;

  protected $fillable = [
    'hasil_hutan_bukan_kayu_id',
    'bukan_kayu_id',
    'annual_volume_realization',
    'unit',
  ];

  public function hasilHutanBukanKayu()
  {
    return $this->belongsTo(HasilHutanBukanKayu::class, 'hasil_hutan_bukan_kayu_id');
  }

  public function bukanKayu()
  {
    return $this->belongsTo(BukanKayu::class, 'bukan_kayu_id');
  }
}
