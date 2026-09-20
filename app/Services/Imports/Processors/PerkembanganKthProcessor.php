<?php

namespace App\Services\Imports\Processors;

use App\Models\PerkembanganKth;
use Illuminate\Support\Facades\DB;

class PerkembanganKthProcessor extends BaseImportProcessor
{
    /**
     * Array to cache m_villages lookups
     * @var array
     */
    protected array $villageCache = [];

    protected function processRow(array $row, \App\Models\ImportBatch $batch): mixed
    {
        $kabupatenInfo = $row['nama_kabupaten'] ?? $row['kabupatenkota'] ?? null;
        $kecamatanInfo = $row['nama_kecamatan'] ?? $row['kecamatan'] ?? null;
        $desaInfo = trim($row['nama_desa'] ?? $row['desa'] ?? '');
        $bulanInfo = $row['bulan_angka'] ?? $row['bulan_1_12'] ?? $row['bulan'] ?? null;
        $kelasInfo = $row['kelas_kelembagaan'] ?? $row['kelas_kelembagaan_pemulamadyautama'] ?? 'pemula';
        
        $luasInfo = $row['luas_kelola_ha'] ?? $row['luas_kelola'] ?? 0;
        $luasKelola = floatval(str_replace(',', '.', (string)$luasInfo));
        $jumlahAnggota = floatval(str_replace(',', '.', (string)($row['jumlah_anggota'] ?? 0)));

        $regency = $this->findRegency($kabupatenInfo);
        $district = $this->findDistrict($kecamatanInfo, $regency?->id);

        $villageId = null;
        if (!empty($desaInfo) && $district) {
            $cacheKey = "{$district->id}_" . strtolower($desaInfo);
            if (array_key_exists($cacheKey, $this->villageCache)) {
                $villageId = $this->villageCache[$cacheKey];
            } else {
                $village = DB::table('m_villages')
                    ->where('district_id', $district->id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($desaInfo) . '%'])
                    ->first();
                $villageId = $village?->id;
                $this->villageCache[$cacheKey] = $villageId;
            }
        }

        PerkembanganKth::create([
            'year' => $row['tahun'],
            'month' => $bulanInfo,
            'nama_kth' => $row['nama_kth'],
            'regency_id' => $regency?->id,
            'district_id' => $district?->id,
            'village_id' => $villageId,
            'province_id' => 35,
            'nomor_register' => $row['nomor_register'] ?? null,
            'kelas_kelembagaan' => strtolower(trim($kelasInfo)),
            'jumlah_anggota' => $jumlahAnggota,
            'luas_kelola' => $luasKelola,
            'potensi_kawasan' => $row['potensi_kawasan'] ?? null,
            'status' => 'draft',
            'created_by' => $batch->user_id,
        ]);
        
        return true;
    }
}
