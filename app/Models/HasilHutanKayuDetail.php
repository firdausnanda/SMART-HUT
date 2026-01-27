<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilHutanKayuDetail extends Model
{
  use HasFactory;

  protected $fillable = [
    'hasil_hutan_kayu_id',
    'kayu_id',
  ];

  public function hasilHutanKayu()
  {
    return $this->belongsTo(HasilHutanKayu::class, 'hasil_hutan_kayu_id');
  }

  public function kayu()
  {
    return $this->belongsTo(Kayu::class, 'kayu_id');
  }
}
