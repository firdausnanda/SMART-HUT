<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportStagingRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'data_payload',
        'status',
        'validation_errors',
    ];

    protected $casts = [
        'data_payload' => 'array',
        'validation_errors' => 'array',
    ];

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
