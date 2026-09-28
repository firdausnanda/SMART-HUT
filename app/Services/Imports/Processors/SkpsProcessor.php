<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\Skps;
use Illuminate\Support\Facades\DB;

class SkpsProcessor extends BaseImportProcessor
{
    private static $skemas = null;

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();
        self::$skemas ??= DB::table('m_skema_perhutanan_sosial')->get();
    }

    public function process(ImportBatch $batch): int
    {
        self::$regencies = null;
        self::$districts = null;
        self::$skemas = null;

        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $regency = $this->findRegency($row['nama_kabupatenkota'] ?? '');
        if (!$regency) return false;

        $district = $this->findDistrict($row['nama_kecamatan'] ?? '', $regency->id);
        if (!$district || (int) $district->regency_id !== (int) $regency->id) return false;

        $skemaName = strtolower(trim((string) ($row['nama_skema_perhutanan_sosial'] ?? '')));
        if ($skemaName === '') return false;
        $skema = self::$skemas->first(fn($item) => str_contains(strtolower($item->name), $skemaName));
        if (!$skema) return false;

        return Skps::create([
            'cdk_id' => $this->importCdkId,
            'province_id' => $regency->province_id,
            'regency_id' => $regency->id,
            'district_id' => $district->id,
            'id_skema_perhutanan_sosial' => $skema->id,
            'nama_kelompok' => $row['nama_kelompok'],
            'potential' => $row['potensi'] ?? $row['potensi_ha'],
            'ps_area' => $row['luas_ps_ha'],
            'number_of_kk' => (int) $row['jumlah_kk'],
            'status' => 'draft',
            'created_by' => $batch->user_id,
        ]);
    }
}
