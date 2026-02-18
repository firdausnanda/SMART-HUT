<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengelolaPS extends Model
{
  use HasFactory;

  protected $table = 'm_pengelola_ps';

  protected $fillable = ['name'];
}
