<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Contracts\Workflowable;
use Illuminate\Database\Eloquent\Builder;

class Bezetting extends Model implements Workflowable
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $fillable = [
        'nama_jabatan',
        'kebutuhan',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
    ];

    public function pegawais()
    {
        return $this->hasMany(Pegawai::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public static function baseQuery(array $ids): Builder
    {
        return static::query()->whereIn('id', $ids);
    }

    public static function workflowMap(): array
    {
        return [
            'submit' => [
                'pelaksana' => [
                    'from' => ['draft', 'rejected'],
                    'to' => 'waiting_kasi',
                ],
                'pk' => [
                    'from' => ['draft', 'rejected'],
                    'to' => 'waiting_kasi',
                ],
                'peh' => [
                    'from' => ['draft', 'rejected'],
                    'to' => 'waiting_kasi',
                ],
            ],

            'approve' => [
                'kasi' => [
                    'from' => 'waiting_kasi',
                    'to' => 'waiting_cdk',
                    'timestamp' => 'approved_by_kasi_at',
                ],
                'kacdk' => [
                    'from' => 'waiting_cdk',
                    'to' => 'final',
                    'timestamp' => 'approved_by_cdk_at',
                ],
            ],

            'reject' => [
                'admin' => [],
                'kasi' => [
                    'from' => 'waiting_kasi',
                ],
                'kacdk' => [
                    'from' => 'waiting_cdk',
                ],
            ],

            'delete' => [
                'admin' => [
                    'delete' => true,
                ],
                'pelaksana' => [
                    'from' => 'draft',
                    'delete' => true,
                ],
                'pk' => [
                    'from' => 'draft',
                    'delete' => true,
                ],
                'peh' => [
                    'from' => 'draft',
                    'delete' => true,
                ],
            ],
        ];
    }
}
