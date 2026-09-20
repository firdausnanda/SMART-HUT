<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\RealisasiPnbp;
use Illuminate\Support\Facades\DB;

class RealisasiPnbpProcessor extends BaseImportProcessor
{
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
        self::$pengelolaWisata = null;

        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $kabupatenInfo = $row['nama_kabupatenkota'] ?? $row['nama_kabupaten'] ?? null;
        $pwInfo        = $row['nama_pengelola_wisata'] ?? null;

        $regency = $this->findRegency($kabupatenInfo ?? '');
        if (!$regency) return false;

        $pengelolaWisataId = null;
        if (!empty($pwInfo)) {
            $key = strtolower(trim($pwInfo));
            $pw  = self::$pengelolaWisata->first(fn($p) => str_contains($p->name_lower ?? strtolower($p->name), $key));
            $pengelolaWisataId = $pw?->id;
        }

        $bulan = $row['bulan_angka_1_12'] ?? $row['bulan_angka'] ?? $row['bulan'] ?? null;
        
        $targetStr = $row['target_pnbp'] ?? '0';
        $target = (float) str_replace(',', '.', (string) $targetStr);

        $realisasiStr = $row['realisasi_pnbp'] ?? '0';
        $realisasi = (float) str_replace(',', '.', (string) $realisasiStr);

        $parent = RealisasiPnbp::create([
            'year'                     => $row['tahun'],
            'month'                    => $bulan,
            'province_id'              => $regency->province_id ?? 35,
            'regency_id'               => $regency->id,
            'id_pengelola_wisata'      => $pengelolaWisataId,
            'types_of_forest_products' => $row['jenis_hasil_hutan'] ?? null,
            'pnbp_target'              => $target,
            'pnbp_realization'         => $realisasi,
            'status'                   => 'draft',
            'created_by'               => $batch->user_id,
        ]);

        return $parent;
    }
}
