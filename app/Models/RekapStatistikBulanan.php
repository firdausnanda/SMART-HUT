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

class RekapStatistikBulanan extends Model implements Workflowable
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $table = 'rekap_statistik_bulanan';

    protected $fillable = [
        'periode_tahun',
        'periode_bulan',
        'total_pegawai_aktif',
        'total_pns',
        'total_pppk',
        'total_honorer',
        'total_laki',
        'total_perempuan',
        'total_pensiun_tahun_ini',
        'total_pensiun_bulan_ini',
        'total_pensiun_6_bulan',
        'statistik_status_pegawai',
        'statistik_pendidikan',
        'statistik_golongan',
        'statistik_generasi',
        'statistik_status_pernikahan',
        'statistik_bezetting',
        'statistik_unit_kerja',
        'statistik_masa_kerja',
        'statistik_usia',
        'kgb_jatuh_bulan_ini',
        'kgb_jatuh_3_bulan',
        'dihasilkan_dari',
        'dihasilkan_pada',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'statistik_status_pegawai' => 'array',
        'statistik_pendidikan' => 'array',
        'statistik_golongan' => 'array',
        'statistik_generasi' => 'array',
        'statistik_status_pernikahan' => 'array',
        'statistik_bezetting' => 'array',
        'statistik_unit_kerja' => 'array',
        'statistik_masa_kerja' => 'array',
        'statistik_usia' => 'array',
        'dihasilkan_pada' => 'datetime',
        'approved_by_kasi_at' => 'datetime',
        'approved_by_cdk_at' => 'datetime',
    ];

    public function scopeForYear($query, $year)
    {
        return $query->where('periode_tahun', $year);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
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
