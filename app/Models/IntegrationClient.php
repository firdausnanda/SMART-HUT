<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Sistem eksternal yang membaca data SMART-HUT lewat /api/v1.
 */
class IntegrationClient extends Model
{
    use HasFactory, HasApiTokens, LogsActivity;

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'cdk_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function cdk()
    {
        return $this->belongsTo(Cdk::class);
    }

    public function isProvinceWide(): bool
    {
        return is_null($this->cdk_id);
    }

    /**
     * Dipanggil global scope BelongsToCdk bila model data di-query saat klien
     * integrasi yang sedang terautentikasi. Klien tanpa CDK = tingkat provinsi.
     */
    public function isAdminProvinsi(): bool
    {
        return $this->isProvinceWide();
    }

    /**
     * Batasi query data ke data final dan, bila klien terikat CDK, ke CDK itu.
     * Dipakai oleh semua endpoint data /api/v1.
     */
    public function applyDataScope(Builder $query): Builder
    {
        return $this->applyCdkScope($query)
            ->where($query->getModel()->getTable() . '.status', 'final');
    }

    /**
     * Batasi query ke CDK klien saja (tanpa syarat status). Global scope
     * BelongsToCdk milik user web dilepas agar aturan API berdiri sendiri.
     */
    public function applyCdkScope(Builder $query): Builder
    {
        $query->withoutGlobalScope('cdk_scope');

        if (!$this->isProvinceWide()) {
            $query->where($query->getModel()->getTable() . '.cdk_id', $this->cdk_id);
        }

        return $query;
    }
}
