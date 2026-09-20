<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\Kups;

class KupsProcessor extends BaseImportProcessor
{
    public function process(ImportBatch $batch): int
    {
        self::$regencies = null;
        self::$districts = null;

        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $regency = $this->findRegency($row['nama_kabupatenkota'] ?? '');
        if (!$regency) {
            return false;
        }

        $district = $this->findDistrict($row['nama_kecamatan'] ?? '', $regency->id);
        if (!$district) {
            return false;
        }

        return Kups::create([
            'regency_id' => $regency->id,
            'district_id' => $district->id,
            'nama_kups' => $row['nama_kups'],
            'province_id' => $regency->province_id ?? 35,
            'category' => $row['kategori'] ?? null,
            'commodity' => $row['komoditas'] ?? null,
            'status' => 'draft',
            'created_by' => $batch->user_id,
        ]);
    }
}
