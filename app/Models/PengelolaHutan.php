<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengelolaHutan extends Model
{
  use HasFactory;

  protected $table = 'm_pengelola_hutan';

  protected $fillable = [
    'name',
  ];

  public function hasilHutanKayu()
  {
    return $this->hasMany(HasilHutanKayu::class, 'pengelola_hutan_id');
  }
}
