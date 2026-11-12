<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Integrasi\Angka;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dasar API Resource /api/v1. Format keluaran sengaja tidak mengikuti nama
 * kolom database agar skema tabel bisa berubah tanpa memutus klien.
 * Jangan sertakan created_by, updated_by, deleted_by, atau rejection_note.
 */
abstract class IntegrationResource extends JsonResource
{
    /**
     * Bagian umum semua data workflow: periode, CDK, dan wilayah. Tanpa
     * argumen wilayah, field "wilayah" tidak disertakan (dataset tanpa lokasi).
     */
    protected function common(?Model $provinsi = null, ?Model $kabupaten = null, ?Model $kecamatan = null, ?Model $desa = null, bool $withWilayah = true, bool $withPeriod = true): array
    {
        $data = ['id' => $this->id];

        if ($withPeriod) {
            $data['tahun'] = (int) $this->year;
            $data['bulan'] = (int) $this->month;
        }

        $data['cdk'] = $this->ref($this->whenLoadedModel('cdk'), 'nama');

        if ($withWilayah) {
            $data['wilayah'] = [
                'provinsi' => $this->ref($provinsi),
                'kabupaten' => $this->ref($kabupaten),
                'kecamatan' => $this->ref($kecamatan),
                'desa' => $this->ref($desa),
            ];
        }

        return $data;
    }

    protected function timestamps(): array
    {
        return [
            'difinalkan_pada' => $this->approved_by_cdk_at ? $this->asDateTime($this->approved_by_cdk_at) : null,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * {id, nama} dari model referensi, atau null.
     */
    protected function ref(?Model $model, string $nameColumn = 'name'): ?array
    {
        return $model ? ['id' => $model->getKey(), 'nama' => $model->{$nameColumn}] : null;
    }

    protected function whenLoadedModel(string $relation): ?Model
    {
        return $this->resource->relationLoaded($relation) ? $this->resource->getRelation($relation) : null;
    }

    protected function decimal($value): float
    {
        return round((float) $value, 2);
    }

    /**
     * Angka desimal dari kolom teks yang diinput bebas. Lihat Angka::desimal().
     */
    protected function parseDecimal($value): ?float
    {
        return Angka::desimal($value);
    }

    /**
     * Bilangan bulat dari kolom teks. Lihat Angka::bulat().
     */
    protected function parseInteger($value): ?int
    {
        return Angka::bulat($value);
    }

    private function asDateTime($value): string
    {
        return \Illuminate\Support\Carbon::parse($value)->toIso8601String();
    }
}
