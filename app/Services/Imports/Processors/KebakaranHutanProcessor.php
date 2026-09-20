<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\KebakaranHutan;
use Illuminate\Support\Facades\DB;

class KebakaranHutanProcessor extends BaseImportProcessor
{
    protected static $villageCache = [];
    protected static $pengelolaWisata = null;

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$pengelolaWisata === null) {
            self::$pengelolaWisata = DB::table('m_pengelola_wisata')->get()
                ->keyBy(fn($p) => strtolower(trim($p->name)));
        }
    }

    public function process(ImportBatch $batch): int
    {
        self::$regencies       = null;
        self::$districts       = null;
        self::$villageCache    = [];
        self::$pengelolaWisata = null;

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
        $pwInfo        = $row['nama_pengelola_wisata'] ?? null;

        $regency = $this->findRegency($kabupatenInfo ?? '');
        if (!$regency) return false;

        $district = $this->findDistrict($kecamatanInfo ?? '', $regency->id);
        if (!$district) return false;

        $village = $this->getVillage($district->id, $desaInfo ?? '');
        if (!$village) return false;

        $pengelolaWisataId = null;
        if (!empty($pwInfo)) {
            $key = strtolower(trim($pwInfo));
            $pw  = self::$pengelolaWisata->first(fn($p) => str_contains($p->name_lower ?? strtolower($p->name), $key));
            $pengelolaWisataId = $pw?->id;
        }

        $bulan = $row['bulan_angka_1_12'] ?? $row['bulan_angka'] ?? $row['bulan'] ?? null;
        
        $luasStr = $row['luas_kebakaran_ha'] ?? '0';
        $luas = (float) str_replace(',', '.', (string) $luasStr);

        $parent = KebakaranHutan::create([
            'year'                => $row['tahun'],
            'month'               => $bulan,
            'province_id'         => 35,
            'regency_id'          => $regency->id,
            'district_id'         => $district->id,
            'village_id'          => $village->id,
            'id_pengelola_wisata' => $pengelolaWisataId,
            'area_function'       => $row['fungsi_kawasan'] ?? null,
            'number_of_fires'     => (int) ($row['jumlah_kejadian'] ?? 0),
            'fire_area'           => $luas,
            'status'              => 'draft',
            'created_by'          => $batch->user_id,
        ]);

        return $parent;
    }
}
