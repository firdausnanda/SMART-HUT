<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class KebakaranHutanImportValidator
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

        if (empty($row["nama_kabupatenkota"])) $errors[] = "Nama Kabupaten/Kota harus diisi.";
        if (empty($row["nama_kecamatan"])) $errors[] = "Nama Kecamatan harus diisi.";
        if (empty($row["nama_desa"])) $errors[] = "Nama Desa harus diisi.";
        if (empty($row["nama_pengelola_wisata"])) $errors[] = "Nama Pengelola Wisata harus diisi.";
        if (empty($row["fungsi_kawasan"])) $errors[] = "Fungsi Kawasan harus diisi.";

        if (!isset($row["jumlah_kejadian"]) || $row["jumlah_kejadian"] === "") {
            $errors[] = "Jumlah Kejadian harus diisi.";
        } elseif (!is_numeric($row["jumlah_kejadian"]) || $row["jumlah_kejadian"] < 0) {
            $errors[] = "Jumlah Kejadian harus berupa angka dan minimal 0.";
        }

        if (!isset($row["luas_kebakaran_ha"]) || $row["luas_kebakaran_ha"] === "") {
            $errors[] = "Luas Kebakaran (Ha) harus diisi.";
        } elseif (!is_numeric($row["luas_kebakaran_ha"])) {
            $errors[] = "Luas Kebakaran (Ha) harus berupa angka.";
        }

        if (!empty($errors)) return $errors;

        $regency = DB::table("m_regencies")
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_kabupatenkota"])) . "%"])
            ->first();

        if (!$regency) {
            return ["Kabupaten/Kota \"" . $row["nama_kabupatenkota"] . "\" tidak ditemukan."];
        }

        $district = DB::table("m_districts")
            ->where("regency_id", $regency->id)
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_kecamatan"])) . "%"])
            ->first();

        if (!$district) {
            return ["Kecamatan \"" . $row["nama_kecamatan"] . "\" tidak ditemukan di " . $regency->name . "."];
        }

        $village = DB::table("m_villages")
            ->where("district_id", $district->id)
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_desa"])) . "%"])
            ->first();

        if (!$village) {
            return ["Desa \"" . $row["nama_desa"] . "\" tidak ditemukan di Kec. " . $district->name . "."];
        }

        $pengelolaWisata = DB::table("m_pengelola_wisata")
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_pengelola_wisata"])) . "%"])
            ->first();

        if (!$pengelolaWisata) {
            return ["Pengelola Wisata \"" . $row["nama_pengelola_wisata"] . "\" tidak ditemukan."];
        }

        return true;
    }
}

