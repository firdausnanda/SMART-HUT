<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\RehabManggrove;
use Illuminate\Support\Facades\DB;

class RehabManggroveProcessor extends BaseImportProcessor
{
    protected static $villageCache = [];

    public function process(ImportBatch $batch): int
    {
        self::$regencies    = null;
        self::$districts    = null;
        self::$villageCache = [];

        return parent::process($batch);
    }

    protected function getVillage($districtId, $name)
    {
        if (trim($name) === '') return null;

        $nameLower = strtolower(trim($name));
        $cacheKey = $districtId . '_' . $nameLower;

        if (!array_key_exists($cacheKey, self::$villageCache)) {
            $village = DB::table('m_villages')
                ->where('district_id', $districtId)
                ->where('name', 'like', '%' . $nameLower . '%')
                ->first();
            
            self::$villageCache[$cacheKey] = $village;
        }

        return self::$villageCache[$cacheKey];
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $kabupatenInfo = $row['nama_kabupaten'] ?? null;
        $kecamatanInfo = $row['nama_kecamatan'] ?? null;
        $desaInfo      = $row['nama_desa'] ?? null;

        $regency = $this->findRegency($kabupatenInfo ?? '');
        if (!$regency) return false;

        $district = $this->findDistrict($kecamatanInfo ?? '', $regency->id);
        if (!$district) return false;

        $village = $this->getVillage($district->id, $desaInfo ?? '');

        $targetHaStr = $row['target_tahunan_ha'] ?? '0';
        $targetHa = (float) str_replace(',', '.', (string) $targetHaStr);
        
        $realisasiHaStr = $row['realisasi_ha'] ?? '0';
        $realisasiHa = (float) str_replace(',', '.', (string) $realisasiHaStr);

        $fundSource = trim($row['sumber_dana'] ?? '');
        $fundSource = $fundSource ? strtolower($fundSource) : 'other';

        $bulan = $row['bulan_angka'] ?? $row['bulan_angka_1_12'] ?? null;

        $parent = RehabManggrove::create([
            'year'        => $row['tahun'] ?? null,
            'month'       => $bulan,
            'province_id' => 35,
            'regency_id'  => $regency->id,
            'district_id' => $district->id,
            'village_id'  => $village?->id,
            'fund_source' => $fundSource,
            'target_annual' => $targetHa,
            'realization'   => $realisasiHa,
            'status'        => 'draft',
            'created_by'    => $batch->user_id,
        ]);

        return $parent;
    }
}
