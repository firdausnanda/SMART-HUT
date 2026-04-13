<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pegawai extends Model
{
    use HasFactory, Userstamps, LogsActivity;

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
        // 1. Cek riwayat KGB terakhir yang sudah final
        $latestKgb = $this->riwayatKgb()->where('status', 'final')->latest('tmt_kgb')->first();

        if ($latestKgb && $latestKgb->tmt_kgb_berikutnya) {
            $baseDate = $latestKgb->tmt_kgb_berikutnya;
        } else {
            // 2. Jika belum ada riwayat, gunakan TMT CPNS atau PNS sebagai basis
            $baseDate = $this->tmt_cpns ?? $this->tmt_pns;

            if (!$baseDate) {
                return null;
            }

            // Basis pertama adalah 2 tahun setelah CPNS/PNS
            $baseDate = $baseDate->copy()->addYears(2);
        }

        // 3. Hitung proyeksi terdekat atau yang sesuai dengan siklus 2 tahunan
        // Jika basis sudah di masa depan, itu adalah proyeksi berikutnya
        if ($baseDate->isFuture()) {
            return $baseDate;
        }

        // Jika basis di masa lalu, hitung siklus 2 tahunan sampai mencapai setidaknya tahun ini
        $now = now();
        $yearsPassed = $baseDate->diffInYears($now);
        $cycles = (int) ceil($yearsPassed / 2);

        // Selalu pastikan setidaknya 1 siklus jika basis sudah di masa lalu
        if ($cycles < 1 && $baseDate->isPast()) {
            $cycles = 1;
        }

        return $baseDate->copy()->addYears($cycles * 2);
    }

    /**
     * Menghitung proyeksi KGB pada tahun tertentu (untuk analisa/proyeksi)
     */
    public function getKgbProjectionForYear($year)
    {
        $latestKgb = $this->riwayatKgb()->where('status', 'final')->latest('tmt_kgb')->first();

        if ($latestKgb && $latestKgb->tmt_kgb_berikutnya) {
            $baseDate = $latestKgb->tmt_kgb_berikutnya;
        } else {
            $baseDate = $this->tmt_cpns ?? $this->tmt_pns;
            if (!$baseDate)
                return null;
            $baseDate = $baseDate->copy()->addYears(2);
        }

        // Hitung selisih tahun antara basis dan target tahun
        $diffYears = $year - $baseDate->year;

        // Jika target tahun di masa lalu dari basis
        if ($diffYears < 0)
            return null;

        // KGB terjadi setiap 2 tahun. Cek apakah target tahun masuk dalam siklus.
        if ($diffYears % 2 === 0) {
            return $baseDate->copy()->year($year);
        }

        return null;
    }

    /**
     * Proyeksi Tanggal Pensiun
     */
    public function getRetirementDateAttribute()
    {
        if (!$this->tanggal_lahir) {
            return null;
        }

        $bup = (int) ($this->bup ?: 58);

        // Selain tanggal 1, pensiun adalah awal bulan berikutnya setelah mencapai BUP
        return $this->tanggal_lahir->copy()->addYears($bup)->endOfMonth()->addDay();
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
        if (!$this->tanggal_lahir)
            return 'Unknown';
        $year = $this->tanggal_lahir->year;

        if ($year >= 1997 && $year <= 2012)
            return 'Gen Z';
        if ($year >= 1981 && $year <= 1996)
            return 'Milenial';
        if ($year >= 1965 && $year <= 1980)
            return 'Gen X';
        if ($year < 1965)
            return 'Baby Boomer';
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
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: pegawai yang pensiun dalam N hari ke depan
     * Dihitung dari tanggal_lahir + bup (Batas Usia Pensiun)
     */
    public function scopePensiunDalam($query, int $days)
    {
        $today = now()->format('Y-m-d');
        $limit = now()->addDays($days)->format('Y-m-d');
        return $query
            ->whereNotNull('tanggal_lahir')
            ->whereNotNull('bup')
            ->where('status_kedudukan', 'Aktif')
            ->whereRaw(
                "DATE_ADD(tanggal_lahir, INTERVAL bup YEAR) BETWEEN ? AND ?",
                [$today, $limit]
            )
            ->orderByRaw("DATE_ADD(tanggal_lahir, INTERVAL bup YEAR) ASC");
    }

    /**
     * Scope: pegawai yang KGB-nya jatuh pada bulan & tahun tertentu
     * Default: bulan & tahun saat ini
     */
    public function scopeKgbJatuhPadaBulan($query, ?int $month = null, ?int $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        return $query->whereHas('latestKgb', function ($q) use ($month, $year) {
            $q->whereMonth('tmt_kgb_berikutnya', $month)
                ->whereYear('tmt_kgb_berikutnya', $year);
        });
    }

}
