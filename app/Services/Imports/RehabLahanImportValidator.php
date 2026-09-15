<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class RehabLahanImportValidator
{
    /**
     * @param array $row
     * @param int $rowNumber
     * @return bool|array
     */
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row['tahun'])) $errors[] = 'Tahun harus diisi.';
        elseif (!is_numeric($row['tahun'])) $errors[] = 'Tahun harus berupa angka.';

        if (empty($row['bulan_angka'])) $errors[] = 'Bulan harus diisi.';
        elseif (!is_numeric($row['bulan_angka']) || $row['bulan_angka'] < 1 || $row['bulan_angka'] > 12) {
            $errors[] = 'Bulan harus berupa angka 1-12.';
        }

        if (empty($row['nama_kabupaten'])) $errors[] = 'Nama Kabupaten harus diisi.';
        if (empty($row['nama_kecamatan'])) $errors[] = 'Nama Kecamatan harus diisi.';
        
        if (!isset($row['target_tahunan_ha']) || $row['target_tahunan_ha'] === '') {
            $errors[] = 'Target Tahunan (Ha) harus diisi.';
        } elseif (!is_numeric($row['target_tahunan_ha'])) {
            $errors[] = 'Target Tahunan (Ha) harus berupa angka.';
        }

        if (!isset($row['realisasi_ha']) || $row['realisasi_ha'] === '') {
            $errors[] = 'Realisasi (Ha) harus diisi.';
        } elseif (!is_numeric($row['realisasi_ha'])) {
            $errors[] = 'Realisasi (Ha) harus berupa angka.';
        }

        if (empty($row['sumber_dana'])) {
            $errors[] = 'Sumber Dana harus diisi.';
        } else {
            $sumberDana = DB::table('m_sumber_dana')
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($row['sumber_dana']))])
                ->exists();
            if (!$sumberDana) {
                $errors[] = "Sumber Dana '{$row['sumber_dana']}' tidak ditemukan di Master Sumber Dana.";
            }
        }

        if (!empty($errors)) return $errors;

        $regency = DB::table('m_regencies')
            ->where('province_id', 35)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kabupaten'])) . '%'])
            ->first();
            
        if (!$regency) {
            return ["Kabupaten '{$row['nama_kabupaten']}' tidak ditemukan di Jawa Timur."];
        }

        $district = DB::table('m_districts')
            ->where('regency_id', $regency->id)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
            ->first();
            
        if (!$district) {
            // Fallback just like original code
            $district = DB::table('m_districts')
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
                ->first();
                
            if (!$district) {
                return ["Kecamatan '{$row['nama_kecamatan']}' tidak ditemukan."];
            }
        }

        return true;
    }
}

