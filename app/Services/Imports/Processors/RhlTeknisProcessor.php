<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\RhlTeknis;
use App\Models\RhlTeknisDetail;
use Illuminate\Support\Facades\DB;

class RhlTeknisProcessor extends BaseImportProcessor
{
    protected static $villageCache = [];
    protected static $bangunanKta = null;

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$bangunanKta === null) {
            self::$bangunanKta = DB::table('m_bangunan_kta')->get()
                ->keyBy(fn($b) => strtolower(trim($b->name)));
        }
    }

    public function process(ImportBatch $batch): int
    {
        self::$regencies    = null;
        self::$districts    = null;
        self::$villageCache = [];
        self::$bangunanKta  = null;

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
        $kabupatenInfo = $row['kabupaten'] ?? null;
        $kecamatanInfo = $row['kecamatan'] ?? null;
        $desaInfo      = $row['desa'] ?? null;

        $regency = $this->findRegency($kabupatenInfo ?? '');
        if (!$regency) return false;

        $district = $this->findDistrict($kecamatanInfo ?? '', $regency->id);
        if (!$district) return false;

        $village = $this->getVillage($district->id, $desaInfo ?? '');

        $targetUnitStr = $row['target_tahunan_unit'] ?? '0';
        $targetUnit = (float) str_replace(',', '.', (string) $targetUnitStr);

        $fundSource = trim($row['sumber_dana'] ?? '');
        $fundSource = $fundSource ? strtolower($fundSource) : 'other';

        $bulan = $row['bulan_angka'] ?? $row['bulan_angka_1_12'] ?? null;

        $rhlTeknis = RhlTeknis::create([
            'year'        => $row['tahun'] ?? null,
            'month'       => $bulan,
            'province_id' => 35,
            'regency_id'  => $regency->id,
            'district_id' => $district->id,
            'village_id'  => $village?->id,
            'fund_source' => $fundSource,
            'target_annual' => $targetUnit,
            'status'        => 'draft',
            'created_by'    => $batch->user_id,
        ]);

        $types = isset($row['jenis_bangunan']) ? array_map('trim', explode(',', $row['jenis_bangunan'])) : [];
        $units = isset($row['jumlah_unit']) ? array_map('trim', explode(',', $row['jumlah_unit'])) : [];

        foreach ($types as $index => $typeName) {
            $key = strtolower($typeName);
            $bangunan = self::$bangunanKta->get($key);
            
            if (!$bangunan) {
                // Try partial match if exact match fails
                $bangunan = self::$bangunanKta->first(fn($b) => str_contains($b->name_lower ?? strtolower($b->name), $key));
            }

            if ($bangunan && isset($units[$index])) {
                $unitAmountStr = $units[$index];
                $unitAmount = (float) str_replace(',', '.', (string) $unitAmountStr);

                RhlTeknisDetail::create([
                    'rhl_teknis_id'   => $rhlTeknis->id,
                    'bangunan_kta_id' => $bangunan->id,
                    'unit_amount' => $unitAmount,
                ]);
            }
        }

        return $rhlTeknis;
    }
}
