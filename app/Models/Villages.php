<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Villages extends Model
{
    use HasFactory;

    protected $fillable = [
        "id",
        "district_id",
        "name"
    ];

    public $incrementing = false;

    protected $table = "m_villages";

    public $timestamps = false;

    public function district()
    {
        return $this->belongsTo(Districts::class, "district_id");
    }
}
