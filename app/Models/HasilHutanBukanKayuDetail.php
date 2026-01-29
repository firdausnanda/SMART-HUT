<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilHutanBukanKayuDetail extends Model
{
  use HasFactory;

  protected $fillable = [
    'hasil_hutan_bukan_kayu_id',
    'commodity_id',
    'annual_volume_realization',
    'unit',
  ];

  public function hasilHutanBukanKayu()
  {
    return $this->belongsTo(HasilHutanBukanKayu::class, 'hasil_hutan_bukan_kayu_id');
  }

  public function commodity()
  {
    return $this->belongsTo(Commodity::class, 'commodity_id');
  }
}
