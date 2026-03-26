<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Contracts\Workflowable;

class RiwayatKgb extends Model implements Workflowable
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $table = 'riwayat_kgbs';

    protected $fillable = [
        'pegawai_id',
        'no_sk',
        'tanggal_sk',
        'tmt_kgb',
        'gaji_pokok_baru',
        'tmt_kgb_berikutnya',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal_sk' => 'date',
        'tmt_kgb' => 'date',
        'tmt_kgb_berikutnya' => 'date',
        'approved_by_kasi_at' => 'datetime',
        'approved_by_cdk_at' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public static function baseQuery(array $ids): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()->whereIn('id', $ids);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public static function workflowMap(): array
    {
        return [
            'submit' => [
                'pelaksana' => [
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
                'kasi' => ['from' => 'waiting_kasi'],
                'kacdk' => ['from' => 'waiting_cdk'],
            ],
        ];
    }
}
