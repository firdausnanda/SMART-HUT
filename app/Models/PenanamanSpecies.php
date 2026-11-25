<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenanamanSpecies extends Model
{
    protected $table = 'penanaman_species';
    protected $fillable = ['species_name', 'batang'];
    protected $casts = ['batang' => 'integer'];
}
