<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class RealisasiPnbpImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (!isset($row["tahun"]) || trim((string)$row["tahun"]) === "") {
            $errors[] = "Tahun harus diisi.";
        } elseif (!is_numeric($row["tahun"])) {
            $errors[] = "Tahun harus berupa angka.";
        }

        if (!isset($row["bulan_angka_1_12"]) || trim((string)$row["bulan_angka_1_12"]) === "") {
            $errors[] = "Bulan harus diisi.";
        } elseif (!is_numeric($row["bulan_angka_1_12"]) || $row["bulan_angka_1_12"] < 1 || $row["bulan_angka_1_12"] > 12) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }
        
        if (!isset($row["nama_kabupatenkota"]) || trim((string)$row["nama_kabupatenkota"]) === "") {
            $errors[] = "Nama Kabupaten/Kota harus diisi.";
        } else {
            $regency = DB::table("m_regencies")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_kabupatenkota"])) . "%"])
                ->exists();
            if (!$regency) {
                $errors[] = "Kabupaten/Kota \"" . $row["nama_kabupatenkota"] . "\" tidak ditemukan.";
            }
        }

        if (!isset($row["nama_pengelola_wisata"]) || trim((string)$row["nama_pengelola_wisata"]) === "") {
            $errors[] = "Nama Pengelola Wisata harus diisi.";
        } else {
            $pengelolaWisata = DB::table("m_pengelola_wisata")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_pengelola_wisata"])) . "%"])
                ->exists();
            if (!$pengelolaWisata) {
                $errors[] = "Pengelola Wisata \"" . $row["nama_pengelola_wisata"] . "\" tidak ditemukan.";
            }
        }

        if (!isset($row["jenis_hasil_hutan"]) || trim((string)$row["jenis_hasil_hutan"]) === "") {
            $errors[] = "Jenis Hasil Hutan harus diisi.";
        } elseif (!in_array(trim($row["jenis_hasil_hutan"]), ["Kayu", "Non Kayu", "Jasa Lingkungan"])) {
            $errors[] = "Jenis Hasil Hutan harus salah satu dari: Kayu, Non Kayu, Jasa Lingkungan.";
        }

        if (!isset($row["target_pnbp"]) || trim((string)$row["target_pnbp"]) === "") {
            $errors[] = "Target PNBP harus diisi.";
        }

        if (!isset($row["realisasi_pnbp"]) || trim((string)$row["realisasi_pnbp"]) === "") {
            $errors[] = "Realisasi PNBP harus diisi.";
        }

        if (!empty($errors)) return $errors;
        return true;
    }
}

