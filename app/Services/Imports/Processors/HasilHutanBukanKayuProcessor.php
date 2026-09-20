<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\HasilHutanBukanKayu;
use App\Models\HasilHutanBukanKayuDetail;
use App\Models\BukanKayu;
use App\Models\PengelolaHutan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HasilHutanBukanKayuProcessor extends BaseImportProcessor
{
    private static $allBukanKayu    = null;
    private static $pengelolaHutan  = null;
    private static $pengelolaWisata = null;

    protected string $forestType = '';

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$allBukanKayu === null) {
            $orderedNames = [
                'Bambu', 'Getah Pinus', 'Daun Kayu Putih', 'Porang', 'Kopi',
                'Madu', 'Durian', 'Alpukat', 'Jahe', 'Kunyit'
            ];
            self::$allBukanKayu = BukanKayu::all()->sortBy(function ($model) use ($orderedNames) {
                $index = array_search($model->name, $orderedNames);
                return $index === false ? 9999 + $model->id : $index;
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
        // Extract forestType from module_name e.g. 'hhbk|Hutan Negara'
        $this->forestType = explode('|', $batch->module_name)[1] ?? 'Hutan Negara';

        // Reset static caches for fresh run
        self::$regencies      = null;
        self::$districts      = null;
        self::$allBukanKayu   = null;
        self::$pengelolaHutan = null;
        self::$pengelolaWisata = null;

        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $regency = $this->findRegency($row['nama_kabupaten'] ?? '');
        if (!$regency) return false;

        $districtId = null;
        if ($this->forestType !== 'Hutan Negara' && !empty($row['nama_kecamatan'])) {
            $district   = $this->findDistrict($row['nama_kecamatan'], $regency->id);
            $districtId = $district?->id;
        }

        $pengelolaId = null;
        if ($this->forestType === 'Hutan Negara' && !empty($row['nama_pengelola'])) {
            $key = strtolower(trim($row['nama_pengelola']));
            $ph  = self::$pengelolaHutan->first(fn($p) => str_contains(strtolower($p->name), $key));
            $pengelolaId = $ph?->id;
        }

        $pengelolaWisataId = null;
        if ($this->forestType === 'Perhutanan Sosial' && !empty($row['nama_pengelola_wisata'])) {
            $key = strtolower(trim($row['nama_pengelola_wisata']));
            $pw  = self::$pengelolaWisata->first(fn($p) => str_contains($p->name_lower ?? strtolower($p->name), $key));
            $pengelolaWisataId = $pw?->id;
        }

        $bulan = $row['bulan_1_12'] ?? $row['bulan_angka'] ?? $row['bulan'] ?? null;
        $volumeTargetStr = $row['total_target'] ?? '0';
        $volumeTarget = (float) str_replace(',', '.', (string) $volumeTargetStr);

        $parent = HasilHutanBukanKayu::create([
            'year'                => $row['tahun'],
            'month'               => $bulan,
            'province_id'         => 35,
            'regency_id'          => $regency->id,
            'district_id'         => $this->forestType === 'Hutan Rakyat' ? $districtId : null,
            'pengelola_hutan_id'  => $this->forestType === 'Hutan Negara' ? $pengelolaId : null,
            'pengelola_wisata_id' => $this->forestType === 'Perhutanan Sosial' ? $pengelolaWisataId : null,
            'forest_type'         => $this->forestType,
            'volume_target'       => $volumeTarget,
            'status'              => 'draft',
            'created_by'          => $batch->user_id,
        ]);

        $details = [];
        foreach (self::$allBukanKayu as $commodity) {
            $slug = Str::slug($commodity->name, '_');
            $realizationKey = $slug . '_realisasi';
            $unitKey = $slug . '_satuan';

            if (array_key_exists($realizationKey, $row)) {
                $realizationStr = $row[$realizationKey] ?? '0';
                if ($realizationStr !== '') {
                    $realization = (float) str_replace(',', '.', (string) $realizationStr);
                    
                    $rawUnit = $row[$unitKey] ?? 'kg';
                    $normalizedUnit = strtolower(trim($rawUnit));
                    $unit = \App\Enums\Satuan::tryFrom($normalizedUnit) ? $normalizedUnit : 'lainnya';

                    if ($realization >= 0) {
                        $details[] = [
                            'hasil_hutan_bukan_kayu_id' => $parent->id,
                            'bukan_kayu_id'             => $commodity->id,
                            'annual_volume_realization' => $realization,
                            'unit'                      => $unit,
                            'created_at'                => now(),
                            'updated_at'                => now(),
                        ];
                    }
                }
            }
        }

        if (!empty($details)) {
            HasilHutanBukanKayuDetail::insert($details);
        }

        return $parent;
    }

    public function afterProcess(ImportBatch $batch, int $importedCount): void
    {
        $forestType = explode('|', $batch->module_name)[1] ?? '';
        foreach (range(date('Y'), date('Y') - 5) as $y) {
            cache()->forget("hhbk-stats-{$forestType}-{$y}");
        }
    }
}
