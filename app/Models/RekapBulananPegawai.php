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

class RekapBulananPegawai extends Model implements Workflowable
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $table = 'rekap_bulanan_pegawai';

    protected $fillable = [
        'pegawai_id',
        'periode_tahun',
        'periode_bulan',
        'nip',
        'nama_lengkap',
        'status_pegawai',
        'jenis_kelamin',
        'pendidikan_terakhir',
        'pangkat_golongan',
        'bezetting_id',
        'nama_jabatan_snapshot',
        'unit_kerja',
        'skpd',
        'status_kedudukan',
        'status_pernikahan',
        'generasi',
        'tanggal_lahir',
        'usia_per_periode',
        'tmt_cpns',
        'tmt_pns',
        'masa_kerja_tahun',
        'bup',
        'proyeksi_pensiun',
        'bulan_pensiun_tersisa',
        'gaji_pokok_terakhir',
        'tmt_kgb_berikutnya',
        'sumber_data',
        'status',
        'approved_by_kasi_at',
        'approved_by_cdk_at',
        'rejection_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tmt_cpns' => 'date',
        'tmt_pns' => 'date',
        'proyeksi_pensiun' => 'date',
        'tmt_kgb_berikutnya' => 'date',
        'approved_by_kasi_at' => 'datetime',
        'approved_by_cdk_at' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function bezetting()
    {
        return $this->belongsTo(Bezetting::class);
    }

    public function scopeForPeriode($query, $year, $month)
    {
        return $query->where('periode_tahun', $year)->where('periode_bulan', $month);
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
