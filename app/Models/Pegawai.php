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

class Pegawai extends Model implements Workflowable
{
    use HasFactory, SoftDeletes, Userstamps, LogsActivity;

    protected $fillable = [
        'nip',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'pendidikan_terakhir',
        'status_pegawai',
        'nik',
        'status_pernikahan',
        'alamat',
        'tmt_pns',
        'unit_kerja',
        'skpd',
        'bezetting_id',
        'pangkat_golongan',
        'tmt_cpns',
        'bup',
        'status_kedudukan',
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
        'approved_by_kasi_at' => 'datetime',
        'approved_by_cdk_at' => 'datetime',
    ];

    /**
     * Relasi ke riwayat KGB
     */
    public function riwayatKgb()
    {
        return $this->hasMany(RiwayatKgb::class);
    }

    /**
     * Relasi ke data pensiun
     */
    public function pensiun()
    {
        return $this->hasOne(Pensiun::class);
    }

    /**
     * Get latest KGB (regardless of status if it's final, or even just the latest record)
     */
    public function latestKgb()
    {
        return $this->hasOne(RiwayatKgb::class)->latestOfMany('tmt_kgb');
    }

    /**
     * Proyeksi KGB Berikutnya
     */
    public function getNextKgbDateAttribute()
    {
        $latestKgb = $this->riwayatKgb()->where('status', 'final')->latest('tmt_kgb')->first();
        
        if ($latestKgb && $latestKgb->tmt_kgb_berikutnya) {
            return $latestKgb->tmt_kgb_berikutnya;
        }

        // Jika belum ada riwayat, hitung dari TMT CPNS (biasanya 2 tahun setelah CPNS/PNS)
        $baseDate = $this->tmt_cpns ?? $this->tmt_pns;
        if ($baseDate) {
            return $baseDate->copy()->addYears(2);
        }

        return null;
    }

    /**
     * Proyeksi Tanggal Pensiun
     */
    public function getRetirementDateAttribute()
    {
        if (!$this->tanggal_lahir || !$this->bup) {
            return null;
        }

        // Pensiun biasanya adalah awal bulan berikutnya setelah mencapai BUP
        return $this->tanggal_lahir->copy()->addYears($this->bup)->addMonth(1)->startOfMonth();
    }

    /**
     * Relasi ke tabel bezettings
     */
    public function bezetting()
    {
        return $this->belongsTo(Bezetting::class);
    }

    /**
     * Accessor untuk Generasi berdasarkan tanggal_lahir
     */
    public function getGenerasiAttribute()
    {
        if (!$this->tanggal_lahir) return 'Unknown';
        $year = $this->tanggal_lahir->year;
        
        if ($year >= 1997 && $year <= 2012) return 'Gen Z';
        if ($year >= 1981 && $year <= 1996) return 'Milenial';
        if ($year >= 1965 && $year <= 1980) return 'Gen X';
        if ($year < 1965) return 'Baby Boomer';
        return 'Post-Gen Z';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * Relasi ke tabel users (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class , 'created_by');
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
