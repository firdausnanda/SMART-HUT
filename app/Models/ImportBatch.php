<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ImportBatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'module_name',
        'filename',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stagingRows()
    {
        return $this->hasMany(ImportStagingRow::class);
    }
}
