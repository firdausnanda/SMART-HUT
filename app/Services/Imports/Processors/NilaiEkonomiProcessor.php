<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\Commodity;
use App\Models\NilaiEkonomi;

class NilaiEkonomiProcessor extends BaseImportProcessor
{
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
        self::$commodityCache = null;

        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $regency = $this->findRegency($row['nama_kabupaten'] ?? '');
        if (!$regency) {
            return false;
        }

        $district = $this->findDistrict($row['nama_kecamatan'] ?? '', $regency->id);
        if (!$district) {
            return false;
        }

        $transaction = NilaiEkonomi::create([
            'year' => $row['tahun'],
            'month' => $row['bulan_1_12'],
            'nama_kelompok' => $row['nama_kelompok'],
            'province_id' => 35,
            'regency_id' => $regency->id,
            'district_id' => $district->id,
            'status' => 'draft',
            'created_by' => $batch->user_id,
            'total_transaction_value' => 0,
        ]);

        $commodities = array_map('trim', explode(',', (string) ($row['komoditas'] ?? '')));
        $volumes = array_map('trim', explode(',', (string) ($row['volume_produksi'] ?? '')));
        $satuans = array_map('trim', explode(',', (string) ($row['satuan'] ?? '')));
        $nilais = array_map('trim', explode(',', (string) ($row['nilai_transaksi_rp'] ?? '')));

        $count = count($commodities);

        for ($i = 0; $i < $count; $i++) {
            $commodityName = $commodities[$i] ?? null;
            if (!$commodityName) continue;

            $volumeStr = $volumes[$i] ?? '0';
            if ($volumeStr !== '') {
                $volumeStr = str_replace([' ', "\r", "\n"], '', $volumeStr);
                $volumeStr = str_replace(',', '.', $volumeStr);
                $volume = (float) $volumeStr;
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

            $key = strtolower(trim($commodityName));
            $commodity = self::$commodityCache->get($key);
            if (!$commodity) {
                // If it wasn't strictly found by key, try a str_contains matching
                $commodity = self::$commodityCache->first(function ($c) use ($key) {
                    return str_contains(strtolower($c->name), $key);
                });
            }

            if (!$commodity) continue;

            $transaction->details()->create([
                'commodity_id' => $commodity->id,
                'production_volume' => $volume,
                'satuan' => $satuan,
                'transaction_value' => $nilai,
            ]);

            $transaction->increment('total_transaction_value', $nilai);
        }

        return $transaction;
    }

    private function mapSatuan($satuanRaw)
    {
        $map = [
            'kg' => ['kg', 'kilogram (kg)', 'lg', 'kilogram'],
            'm3' => ['m3', 'meter kubik (m3)', 'meter kubik (m³)', 'meter kubik'],
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
}
