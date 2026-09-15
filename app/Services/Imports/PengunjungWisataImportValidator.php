<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class PengunjungWisataImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row["tahun"])) $errors[] = "Tahun harus diisi.";
        elseif (!is_numeric($row["tahun"])) $errors[] = "Tahun harus berupa angka.";

        if (empty($row["bulan_angka_1_12"])) $errors[] = "Bulan harus diisi.";
        elseif (!is_numeric($row["bulan_angka_1_12"]) || $row["bulan_angka_1_12"] < 1 || $row["bulan_angka_1_12"] > 12) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }

        if (empty($row["nama_pengelola_wisata"])) {
            $errors[] = "Nama Pengelola Wisata harus diisi.";
        }

        if (!isset($row["jumlah_pengunjung"]) || $row["jumlah_pengunjung"] === "") {
            $errors[] = "Jumlah Pengunjung harus diisi.";
        } elseif (!is_numeric($row["jumlah_pengunjung"]) || $row["jumlah_pengunjung"] < 0) {
            $errors[] = "Jumlah Pengunjung harus berupa angka dan minimal 0.";
        }

        if (!isset($row["pendapatan_bruto_rp"]) || $row["pendapatan_bruto_rp"] === "") {
            $errors[] = "Pendapatan Bruto (Rp) harus diisi.";
        } elseif (!is_numeric($row["pendapatan_bruto_rp"]) || $row["pendapatan_bruto_rp"] < 0) {
            $errors[] = "Pendapatan Bruto (Rp) harus berupa angka dan minimal 0.";
        }

        if (!empty($errors)) return $errors;

        $pengelolaWisata = DB::table("m_pengelola_wisata")
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_pengelola_wisata"])) . "%"])
            ->first();

        if (!$pengelolaWisata) {
            return ["Pengelola Wisata \"" . $row["nama_pengelola_wisata"] . "\" tidak ditemukan."];
        }

        return true;
    }
}

