<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\NilaiTransaksiEkonomi;
use App\Models\NilaiTransaksiEkonomiDetail;
use App\Models\Commodity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NilaiTransaksiEkonomiProcessor extends BaseImportProcessor
{
    protected static $villageCache = [];
    protected static $commodityCache = null;

    protected function bootReferenceCache(): void
    {
        parent::bootReferenceCache();

        if (self::$commodityCache === null) {
            self::$commodityCache = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
                ->get()
                ->keyBy(fn($c) => strtolower(trim($c->name)));
        }
    }

    public function process(ImportBatch $batch): int
    {
        self::$regencies = null;
        self::$districts = null;
        self::$villageCache = [];
        self::$commodityCache = null;
        
        return parent::process($batch);
    }

    protected function getVillage($districtId, $name)
    {
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

    private function mapSatuan($satuanRaw)
    {
        $map = [
            'kg' => ['kg', 'kilogram (kg)', 'lg', 'kilogram'],
            'm3' => ['m3', 'meter kubik (m3)', 'meter kubik (m??)', 'meter kubik'],
            'batang' => ['batang', 'batangan', 'bantangan', 'btg'],
            'ton' => ['ton'],
            'pcs' => ['pcs'],
            'buah' => ['buah'],
            'bibit' => ['tanaman', 'bibit'],
            'stup' => ['stup'],
            'orang' => ['orang'],
            'ekor' => ['ekor'],
            'liter' => ['liter'],
            'ikat' => ['ikat'],
            'butir' => ['butir']
        ];
        $lower = strtolower($satuanRaw);
        foreach ($map as $canonical => $variations) {
            if (in_array($lower, $variations)) {
                return $canonical;
            }
        }
        return 'lainnya';
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $kabupatenInfo = $row['nama_kabupaten'] ?? $row['kabupatenkota'] ?? null;
        $kecamatanInfo = $row['nama_kecamatan'] ?? $row['kecamatan'] ?? null;
        $desaInfo      = $row['nama_desa'] ?? $row['desa'] ?? null;
        $bulanInfo     = $row['bulan_1_12'] ?? $row['bulan'] ?? null;

        $regency = $this->findRegency($kabupatenInfo);
        if (!$regency) return false;

        $district = $this->findDistrict($kecamatanInfo, $regency->id);
        if (!$district) return false;

        $village = $this->getVillage($district->id, $desaInfo);
        if (!$village) return false;

        $transaction = NilaiTransaksiEkonomi::create([
            'year'        => $row['tahun'],
            'month'       => $bulanInfo,
            'nama_kth'    => $row['nama_kth'],
            'regency_id'  => $regency?->id,
            'district_id' => $district?->id,
            'village_id'  => $village?->id,
            'province_id' => 35,
            'status'      => 'draft',
            'created_by'  => $batch->user_id,
            'total_nilai_transaksi' => 0,
        ]);



        $commodities = array_map('trim', explode(',', (string) ($row['komoditas'] ?? '')));
        $volumes     = array_map('trim', explode(',', (string) ($row['volume_produksi'] ?? '')));
        $satuans     = array_map('trim', explode(',', (string) ($row['satuan'] ?? '')));
        $nilais      = array_map('trim', explode(',', (string) ($row['nilai_transaksi_rp'] ?? '')));

        $count = count($commodities);
        $detailsToInsert = [];
        $totalNilai = 0;
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            $commodityName = $commodities[$i] ?? null;
            if (!$commodityName) continue;

            $volumeStr = $volumes[$i] ?? '0';
            if ($volumeStr !== '') {
                $volumeStr = str_replace([' ', "\r", "\n"], '', $volumeStr);
                $volume = (float) str_replace(',', '.', $volumeStr);
            } else {
                $volume = 0;
            }

            $nilaiStr = $nilais[$i] ?? '0';
            if ($nilaiStr !== '') {
                $nilaiStr = str_replace([' ', "\r", "\n", '.'], '', $nilaiStr);
                $nilaiStr = str_replace(',', '.', $nilaiStr);
                $nilai = (float) $nilaiStr;
            } else {
                $nilai = 0;
            }

            $satuanRaw = trim($satuans[$i] ?? '-');
            $satuan = $this->mapSatuan($satuanRaw);

            $searchComm = strtolower(trim($commodityName));
            $commodity = self::$commodityCache->get($searchComm);

            if (!$commodity) continue;

            $detailsToInsert[] = [
                'nilai_transaksi_ekonomi_id' => $transaction->id,
                'commodity_id'               => $commodity->id,
                'volume_produksi'            => $volume,
                'satuan'                     => $satuan,
                'nilai_transaksi'            => $nilai,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ];

            $totalNilai += $nilai;
        }

        if (!empty($detailsToInsert)) {
            NilaiTransaksiEkonomiDetail::insert($detailsToInsert);
        }
        $transaction->increment('total_nilai_transaksi', $totalNilai);

        return $transaction;
    }
}
