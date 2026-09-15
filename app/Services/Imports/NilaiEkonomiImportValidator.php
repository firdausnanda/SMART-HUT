<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;
use App\Models\Commodity;

class NilaiEkonomiImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row["tahun"])) $errors[] = "Tahun harus diisi.";
        elseif (!is_numeric($row["tahun"])) $errors[] = "Tahun harus berupa angka.";

        if (empty($row["bulan_1_12"])) $errors[] = "Bulan harus diisi.";
        elseif (!is_numeric($row["bulan_1_12"]) || $row["bulan_1_12"] < 1 || $row["bulan_1_12"] > 12) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }

        $regencyId = null;
        if (!isset($row["nama_kabupaten"]) || trim((string)$row["nama_kabupaten"]) === "") {
            $errors[] = "Nama Kabupaten/Kota harus diisi.";
        } else {
            $regency = DB::table("m_regencies")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["nama_kabupaten"])) . "%"])
                ->first();
            if (!$regency) {
                $errors[] = "Kabupaten/Kota \"" . $row["nama_kabupaten"] . "\" tidak ditemukan.";
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

        if (!isset($row["komoditas"]) || trim((string)$row["komoditas"]) === "") {
            $errors[] = "Komoditas harus diisi.";
        } else {
            $commodities = array_map("trim", explode(",", (string) $row["komoditas"]));
            foreach ($commodities as $commodityName) {
                if (!$commodityName) continue;
                $commodity = Commodity::withoutGlobalScope("not_nilai_transaksi_ekonomi")
                    ->where("name", $commodityName)
                    ->exists();
                if (!$commodity) {
                    $errors[] = "Komoditas \"" . $commodityName . "\" tidak ditemukan.";
                }
            }
        }

        if (!isset($row["volume_produksi"]) || trim((string)$row["volume_produksi"]) === "") {
            $errors[] = "Volume Produksi harus diisi.";
        }

        if (!isset($row["satuan"]) || trim((string)$row["satuan"]) === "") {
            $errors[] = "Satuan harus diisi.";
        }

        if (!isset($row["nilai_transaksi_rp"]) || trim((string)$row["nilai_transaksi_rp"]) === "") {
            $errors[] = "Nilai Transaksi (Rp) harus diisi.";
        }

        if (!empty($errors)) return $errors;
        return true;
    }
}

