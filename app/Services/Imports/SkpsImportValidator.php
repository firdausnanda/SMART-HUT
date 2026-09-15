<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class SkpsImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        $regencyId = null;
        if (!isset($row["nama_kabupatenkota"]) || trim((string)$row["nama_kabupatenkota"]) === "") {
            $errors[] = "Nama Kabupaten/Kota harus diisi.";
        } else {
            $regency = DB::table("m_regencies")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_kabupatenkota"])) . "%"])
                ->first();
            if (!$regency) {
                $errors[] = "Kabupaten/Kota \"" . $row["nama_kabupatenkota"] . "\" tidak ditemukan.";
            } else {
                $regencyId = $regency->id;
            }
        }

        if (!isset($row["nama_kecamatan"]) || trim((string)$row["nama_kecamatan"]) === "") {
            $errors[] = "Nama Kecamatan harus diisi.";
        } elseif ($regencyId) {
            $district = DB::table("m_districts")
                ->where("regency_id", $regencyId)
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_kecamatan"])) . "%"])
                ->exists();
            if (!$district) {
                $errors[] = "Kecamatan \"" . $row["nama_kecamatan"] . "\" tidak ditemukan di kabupaten/kota tersebut.";
            }
        }

        if (!isset($row["nama_kelompok"]) || trim((string)$row["nama_kelompok"]) === "") {
            $errors[] = "Nama Kelompok harus diisi.";
        }

        if (!isset($row["nama_skema_perhutanan_sosial"]) || trim((string)$row["nama_skema_perhutanan_sosial"]) === "") {
            $errors[] = "Nama Skema Perhutanan Sosial harus diisi.";
        } else {
            $skema = DB::table("m_skema_perhutanan_sosial")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_skema_perhutanan_sosial"])) . "%"])
                ->exists();
            if (!$skema) {
                $errors[] = "Skema Perhutanan Sosial \"" . $row["nama_skema_perhutanan_sosial"] . "\" tidak ditemukan.";
            }
        }

        $potensi = $row["potensi"] ?? $row["potensi_ha"] ?? null;
        if (!isset($potensi) || trim((string)$potensi) === "") {
            $errors[] = "Potensi harus diisi.";
        }

        if (!isset($row["luas_ps_ha"]) || trim((string)$row["luas_ps_ha"]) === "") {
            $errors[] = "Luas PS (Ha) harus diisi.";
        } elseif (!is_numeric($row["luas_ps_ha"]) || $row["luas_ps_ha"] < 0) {
            $errors[] = "Luas PS (Ha) harus berupa angka minimal 0.";
        }

        if (!isset($row["jumlah_kk"]) || trim((string)$row["jumlah_kk"]) === "") {
            $errors[] = "Jumlah KK harus diisi.";
        } elseif (!is_numeric($row["jumlah_kk"]) || $row["jumlah_kk"] < 0 || strpos((string)$row["jumlah_kk"], '.') !== false) {
            $errors[] = "Jumlah KK harus berupa angka bulat (tidak boleh desimal) dan minimal 0.";
        }

        if (!empty($errors)) return $errors;
        return true;
    }
}

