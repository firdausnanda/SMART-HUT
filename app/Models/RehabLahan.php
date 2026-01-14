<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;

class RehabLahan extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $table = 'rehab_lahan';

    protected $fillable = [
        'year',
        'month',
        'target_annual',
        'realization',
        'fund_source',
        'village',
        'district',
        'coordinates',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
    ];

    /**
     * Get the status badge color (for frontend usage if needed via API resource)
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'draft' => 'gray',
            'waiting_kasi' => 'yellow',
            'waiting_cdk' => 'blue',
            'final' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
