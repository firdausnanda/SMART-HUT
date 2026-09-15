<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class KupsImportValidator
{
    /**
     * @param array $row
     * @param int $rowNumber
     * @return bool|array
     */
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row['nama_kabupatenkota'])) {
            $errors[] = 'Nama Kabupaten/Kota harus diisi.';
        }
        if (empty($row['nama_kecamatan'])) {
            $errors[] = 'Nama Kecamatan harus diisi.';
        }
        if (empty($row['nama_kups'])) {
            $errors[] = 'Nama KUPS harus diisi.';
        }
        if (empty($row['kategori'])) {
            $errors[] = 'Kategori harus diisi.';
        }
        if (empty($row['komoditas'])) {
            $errors[] = 'Komoditas harus diisi.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $regency = DB::table('m_regencies')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kabupatenkota'])) . '%'])
            ->first();
            
        if (!$regency) {
            return ["Kabupaten/Kota '{$row['nama_kabupatenkota']}' tidak ditemukan."];
        }

        $district = DB::table('m_districts')
            ->where('regency_id', $regency->id)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
            ->first();
            
        if (!$district) {
            return ["Kecamatan '{$row['nama_kecamatan']}' tidak ditemukan di {$regency->name}."];
        }

        return true;
    }
}

