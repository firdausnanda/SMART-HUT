<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class PerkembanganKthImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row["tahun"])) $errors[] = "Tahun harus diisi.";
        elseif (!is_numeric($row["tahun"])) $errors[] = "Tahun harus berupa angka.";

        $bulanInfo = $row["bulan_angka"] ?? $row["bulan_1_12"] ?? $row["bulan"] ?? null;
        if (!empty($bulanInfo) && (!is_numeric($bulanInfo) || $bulanInfo < 1 || $bulanInfo > 12)) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }

        if (empty($row["nama_kth"])) {
            $errors[] = "Nama KTH harus diisi.";
        }

        $kabupatenInfo = $row["nama_kabupaten"] ?? $row["kabupatenkota"] ?? null;
        $kecamatanInfo = $row["nama_kecamatan"] ?? $row["kecamatan"] ?? null;
        $desaInfo = $row["nama_desa"] ?? $row["desa"] ?? null;

        $regencyId = null;
        $districtId = null;

        if ($kabupatenInfo) {
            $regency = DB::table("m_regencies")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($kabupatenInfo)) . "%"])
                ->first();
            if (!$regency) {
                $errors[] = "Kabupaten/Kota \"" . $kabupatenInfo . "\" tidak ditemukan.";
            } else {
                $regencyId = $regency->id;
            }
        }

        if ($kecamatanInfo && $regencyId) {
            $district = DB::table("m_districts")
                ->where("regency_id", $regencyId)
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($kecamatanInfo)) . "%"])
                ->first();
            if (!$district) {
                $errors[] = "Kecamatan \"" . $kecamatanInfo . "\" tidak ditemukan di kabupaten/kota tersebut.";
            } else {
                $districtId = $district->id;
            }
        }

        if ($desaInfo && $districtId) {
            $village = DB::table("m_villages")
                ->where("district_id", $districtId)
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($desaInfo)) . "%"])
                ->exists();
            if (!$village) {
                $errors[] = "Desa/Kelurahan \"" . $desaInfo . "\" tidak ditemukan di kecamatan tersebut.";
            }
        }

        $kelasInfo = $row["kelas_kelembagaan"] ?? $row["kelas_kelembagaan_pemulamadyautama"] ?? null;
        if ($kelasInfo) {
            $kelasInfo = strtolower(trim($kelasInfo));
            if (!in_array($kelasInfo, ["pemula", "madya", "utama"])) {
                $errors[] = "Kelas Kelembagaan harus berupa pemula, madya, atau utama.";
            }
        }

        $luasInfo = $row["luas_kelola_ha"] ?? $row["luas_kelola"] ?? null;
        if ($luasInfo !== null && trim((string)$luasInfo) !== "" && (!is_numeric($luasInfo) || $luasInfo < 0)) {
            $errors[] = "Luas Kelola (Ha) harus berupa angka minimal 0.";
        }

        $jumlahAnggota = $row["jumlah_anggota"] ?? null;
        if ($jumlahAnggota !== null && trim((string)$jumlahAnggota) !== "" && (!is_numeric($jumlahAnggota) || $jumlahAnggota < 0 || strpos((string)$jumlahAnggota, ".") !== false)) {
            $errors[] = "Jumlah Anggota harus berupa angka bulat minimal 0.";
        }

        if (!empty($errors)) return $errors;
        return true;
    }
}

