<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\ReboisasiPS;
use Illuminate\Support\Facades\DB;

class ReboisasiPsProcessor extends BaseImportProcessor
{
    protected static $villageCache = [];
    protected static $pengelolaPs = null;

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$pengelolaPs === null) {
            self::$pengelolaPs = DB::table('m_pengelola_ps')->get()
                ->keyBy(fn($p) => strtolower(trim($p->name)));
        }
    }

    public function process(ImportBatch $batch): int
    {
        self::$regencies    = null;
        self::$districts    = null;
        self::$villageCache = [];
        self::$pengelolaPs  = null;

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
        $kabupatenInfo = $row['nama_kabupatenkota'] ?? $row['nama_kabupaten'] ?? null;
        $kecamatanInfo = $row['nama_kecamatan'] ?? null;
        $desaInfo      = $row['nama_desa'] ?? null;
        $pengelolaInfo = $row['pengelola'] ?? null;

        $regency = $this->findRegency($kabupatenInfo ?? '');
        if (!$regency) return false;

        $district = $this->findDistrict($kecamatanInfo ?? '', $regency->id);
        if (!$district) return false;

        $village = $this->getVillage($district->id, $desaInfo ?? '');

        $pengelolaId = null;
        if (!empty($pengelolaInfo)) {
            $key = strtolower(trim($pengelolaInfo));
            $pengelola = self::$pengelolaPs->first(fn($p) => str_contains($p->name_lower ?? strtolower($p->name), $key));
            $pengelolaId = $pengelola?->id;
        }

        $bulan = $row['bulan_angka_1_12'] ?? $row['bulan_angka'] ?? $row['bulan'] ?? null;

        $targetStr = $row['target_tahunan_ha'] ?? '0';
        $target = (float) str_replace(',', '.', (string) $targetStr);

        $realisasiStr = $row['realisasi_ha'] ?? '0';
        $realisasi = (float) str_replace(',', '.', (string) $realisasiStr);
        
        $sumberDana = !empty($row['sumber_dana']) ? strtolower(trim($row['sumber_dana'])) : 'other';

        $parent = ReboisasiPS::create([
            'year'          => $row['tahun'],
            'month'         => $bulan,
            'province_id'   => 35,
            'regency_id'    => $regency->id,
            'district_id'   => $district->id,
            'village_id'    => $village?->id,
            'pengelola_id'  => $pengelolaId,
            'target_annual' => $target,
            'realization'   => $realisasi,
            'fund_source'   => $sumberDana,
            'status'        => 'draft',
            'created_by'    => $batch->user_id,
        ]);

        return $parent;
    }
}
