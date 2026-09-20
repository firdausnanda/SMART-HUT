<?php

namespace App\Services\Imports\Processors;

use App\Models\PengunjungWisata;
use Illuminate\Support\Facades\DB;

class PengunjungWisataProcessor extends BaseImportProcessor
{
    /**
     * Array to cache m_pengelola_wisata lookups
     * @var array
     */
    protected array $pengelolaWisataCache = [];

    protected function processRow(array $row, \App\Models\ImportBatch $batch): mixed
    {
        $namaPengelola = trim($row['nama_pengelola_wisata'] ?? '');
        $pengelolaWisataId = null;

        if (!empty($namaPengelola)) {
            $cacheKey = strtolower($namaPengelola);
            if (array_key_exists($cacheKey, $this->pengelolaWisataCache)) {
                $pengelolaWisataId = $this->pengelolaWisataCache[$cacheKey];
            } else {
                $pengelolaWisata = DB::table('m_pengelola_wisata')
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . $cacheKey . '%'])
                    ->first();
                $pengelolaWisataId = $pengelolaWisata?->id;
                $this->pengelolaWisataCache[$cacheKey] = $pengelolaWisataId;
            }
        }

        $numberOfVisitors = floatval(str_replace(',', '.', (string)($row['jumlah_pengunjung'] ?? 0)));
        $grossIncome = floatval(str_replace(',', '.', (string)($row['pendapatan_bruto_rp'] ?? 0)));

        PengunjungWisata::create([
            'year' => $row['tahun'],
            'month' => $row['bulan_angka_1_12'],
            'id_pengelola_wisata' => $pengelolaWisataId,
            'number_of_visitors' => $numberOfVisitors,
            'gross_income' => $grossIncome,
            'status' => 'draft',
            'created_by' => $batch->user_id,
        ]);
        
        return true;
    }
}
