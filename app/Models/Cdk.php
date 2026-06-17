<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cdk extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'kepala_nama',
        'alamat',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function regencies()
    {
        return $this->belongsToMany(Regencies::class, 'cdk_regency', 'cdk_id', 'regency_id');
    }

    public function pegawais()
    {
        return $this->hasMany(Pegawai::class);
    }

    public function bezettings()
    {
        return $this->hasMany(Bezetting::class);
    }
}
