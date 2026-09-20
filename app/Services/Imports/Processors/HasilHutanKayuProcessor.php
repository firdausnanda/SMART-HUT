<?php

namespace App\Services\Imports\Processors;

use App\Models\HasilHutanKayu;
use App\Models\HasilHutanKayuDetail;
use App\Models\ImportBatch;
use App\Models\Kayu;
use App\Models\PengelolaHutan;
use App\Models\PengelolaWisata;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HasilHutanKayuProcessor extends BaseImportProcessor
{
    private static $allKayu    = null;
    private static $pengelolaHutan  = null;
    private static $pengelolaWisata = null;

    protected string $forestType = '';

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$allKayu === null) {
            $orderedNames = [
                'Jati', 'Sengon', 'Mahoni', 'Gmelina', 'Sonokeling',
                'Pinus', 'Akasia', 'Mindi', 'Balsa', 'Jabon', 'Kayu Lainnya',
            ];
            self::$allKayu = Kayu::all()->sortBy(function ($m) use ($orderedNames) {
                $idx = array_search($m->name, $orderedNames);
                return $idx === false ? 9999 + $m->id : $idx;
            })->values();
        }

        if (self::$pengelolaHutan === null) {
            self::$pengelolaHutan = DB::table('m_pengelola_hutan')->get()
                ->keyBy(fn($p) => strtolower(trim($p->name)));
        }

        if (self::$pengelolaWisata === null) {
            self::$pengelolaWisata = DB::table('m_pengelola_wisata')->get()
                ->keyBy(fn($p) => strtolower(trim($p->name)));
        }
    }

    public function process(ImportBatch $batch): int
    {
        // Extract forestType from module_name e.g. 'hasil-hutan-kayu|Hutan Negara'
        $this->forestType = explode('|', $batch->module_name)[1] ?? 'Hutan Negara';

        // Reset static caches for fresh run
        self::$regencies      = null;
        self::$districts      = null;
        self::$allKayu        = null;
        self::$pengelolaHutan  = null;
        self::$pengelolaWisata = null;

        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $regency = $this->findRegency($row['nama_kabupaten'] ?? '');
        if (!$regency) return false;

        $districtId = null;
        if ($this->forestType === 'Hutan Rakyat' && !empty($row['nama_kecamatan'])) {
            $district   = $this->findDistrict($row['nama_kecamatan'], $regency->id);
            $districtId = $district?->id;
        }

        $pengelolaWisataId = null;
        if ($this->forestType === 'Perhutanan Sosial' && !empty($row['nama_pengelola_wisata'])) {
            $key = strtolower(trim($row['nama_pengelola_wisata']));
            $pw  = self::$pengelolaWisata->first(fn($p) => str_contains($p->name_lower ?? strtolower($p->name), $key));
            $pengelolaWisataId = $pw?->id;
        }

        $pengelolaHutanId = null;
        if ($this->forestType === 'Hutan Negara' && !empty($row['nama_pengelola_hutan'])) {
            $key = strtolower(trim($row['nama_pengelola_hutan']));
            $ph  = self::$pengelolaHutan->first(fn($p) => str_contains(strtolower($p->name), $key));
            $pengelolaHutanId = $ph?->id;
        }

        $bulan = $row['bulan_1_12'] ?? $row['bulan_angka'] ?? $row['bulan'] ?? null;
        $volumeTargetStr = $row['total_target_m3'] ?? '0';
        $volumeTarget = (float) str_replace(',', '.', (string) $volumeTargetStr);

        $parent = HasilHutanKayu::create([
            'year'                => $row['tahun'],
            'month'               => $bulan,
            'province_id'         => 35,
            'regency_id'          => $regency->id,
            'district_id'         => $districtId,
            'pengelola_hutan_id'  => $pengelolaHutanId,
            'pengelola_wisata_id' => $pengelolaWisataId,
            'forest_type'         => $this->forestType,
            'volume_target'       => $volumeTarget,
            'status'              => 'draft',
            'created_by'          => $batch->user_id,
        ]);

        // Collect details for bulk insert
        $details = [];
        foreach (self::$allKayu as $kayu) {
            $slugName      = Str::slug($kayu->name, '_');
            $realizationKey = $slugName . '_realisasi';
            if (array_key_exists($realizationKey, $row)) {
                $realizationStr = $row[$realizationKey] ?? '0';
                if ($realizationStr !== '') {
                    $realization = (float) str_replace(',', '.', (string) $realizationStr);
                    if ($realization >= 0) {
                        $details[] = [
                            'hasil_hutan_kayu_id' => $parent->id,
                            'kayu_id'             => $kayu->id,
                            'volume_realization'  => $realization,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ];
                    }
                }
            }
        }

        if (!empty($details)) {
            HasilHutanKayuDetail::insert($details);
        }

        return $parent;
    }

    public function afterProcess(ImportBatch $batch, int $importedCount): void
    {
        $forestType = explode('|', $batch->module_name)[1] ?? '';
        foreach (range(date('Y'), date('Y') - 5) as $y) {
            cache()->forget("hhk-stats-{$forestType}-{$y}");
        }
    }
}
