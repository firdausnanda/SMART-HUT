<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\Pbphh;
use Illuminate\Support\Facades\DB;

class PbphhProcessor extends BaseImportProcessor
{
    protected static $jenisProduksiCache = null;

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$jenisProduksiCache === null) {
            self::$jenisProduksiCache = DB::table('m_jenis_produksi')->get();
        }
    }

    public function process(ImportBatch $batch): int
    {
        self::$regencies = null;
        self::$districts = null;
        self::$jenisProduksiCache = null;

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

        $rawJenis = $row['jenis_produksi_kapasitas'] ?? '';
        $items = array_map('trim', explode(',', $rawJenis));
        $pivotData = [];

        foreach ($items as $item) {
            if (trim($item) === '') continue;

            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $item, $matches)) {
                $name = trim($matches[1]);
                $capacity = trim($matches[2]);
            } else {
                $name = $item;
                $capacity = '0';
            }
            $cleanCapacity = str_ireplace(['m3', 'm³'], '', $capacity);
            $cleanCapacity = str_replace(',', '.', $cleanCapacity);
            $capacity = floatval(preg_replace('/[^0-9.]/', '', $cleanCapacity));

            $jenisProduksi = self::$jenisProduksiCache->first(function ($jp) use ($name) {
                return str_contains(strtolower($jp->name), strtolower($name));
            });

            if ($jenisProduksi) {
                $pivotData[$jenisProduksi->id] = ['kapasitas_ijin' => $capacity];
            }
        }

        $condition = strtolower(trim($row['kondisi_saat_ini'] ?? ''));
        $presentCondition = in_array($condition, ['aktif', '1', 'true', 'ya']) ? true : false;

        return DB::transaction(function () use ($row, $batch, $regency, $district, $pivotData, $presentCondition) {
            $pbphh = Pbphh::create([
                'number' => $row['nomor_izin'] ?? '',
                'name' => $row['nama_industri'] ?? '',
                'province_id' => $regency->province_id ?? 35,
                'regency_id' => $regency->id,
                'district_id' => $district->id,
                'investment_value' => (int) ($row['nilai_investasi'] ?? 0),
                'number_of_workers' => (int) ($row['jumlah_tenaga_kerja'] ?? 0),
                'present_condition' => $presentCondition,
                'status' => 'draft',
                'created_by' => $batch->user_id,
            ]);

            $pbphh->jenis_produksi()->attach($pivotData);

            return $pbphh;
        });
    }
}
