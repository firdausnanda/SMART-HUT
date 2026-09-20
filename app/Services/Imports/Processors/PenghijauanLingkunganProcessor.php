<?php

namespace App\Services\Imports\Processors;

use App\Models\PenghijauanLingkungan;
use Illuminate\Support\Facades\DB;

class PenghijauanLingkunganProcessor extends BaseImportProcessor
{
    /**
     * Array to cache m_villages lookups
     * @var array
     */
    protected array $villageCache = [];

    protected function processRow(array $row, \App\Models\ImportBatch $batch): mixed
    {
        $regency = $this->findRegency($row['nama_kabupaten'] ?? null);
        $district = $this->findDistrict($row['nama_kecamatan'] ?? null, $regency?->id);

        $villageId = null;
        $namaDesa = trim($row['nama_desa'] ?? '');
        if (!empty($namaDesa) && $district) {
            $cacheKey = "{$district->id}_" . strtolower($namaDesa);
            if (array_key_exists($cacheKey, $this->villageCache)) {
                $villageId = $this->villageCache[$cacheKey];
            } else {
                $village = DB::table('m_villages')
                    ->where('district_id', $district->id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($namaDesa) . '%'])
                    ->first();
                $villageId = $village?->id;
                $this->villageCache[$cacheKey] = $villageId;
            }
        }

        $targetAnnual = floatval(str_replace(',', '.', (string)($row['target_tahunan_ha'] ?? 0)));
        $realization = floatval(str_replace(',', '.', (string)($row['realisasi_ha'] ?? 0)));

        PenghijauanLingkungan::create([
            'year' => $row['tahun'],
            'month' => $row['bulan_angka'],
            'regency_id' => $regency?->id,
            'district_id' => $district?->id,
            'village_id' => $villageId,
            'province_id' => 35,
            'target_annual' => $targetAnnual,
            'realization' => $realization,
            'fund_source' => !empty($row['sumber_dana']) ? strtolower(trim($row['sumber_dana'])) : 'other',
            'status' => 'draft',
            'created_by' => $batch->user_id,
        ]);
        
        return true;
    }
}
