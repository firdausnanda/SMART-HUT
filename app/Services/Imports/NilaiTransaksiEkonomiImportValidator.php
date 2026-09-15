<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;
use App\Models\Commodity;

class NilaiTransaksiEkonomiImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row["tahun"])) $errors[] = "Tahun harus diisi.";
        elseif (!is_numeric($row["tahun"])) $errors[] = "Tahun harus berupa angka.";

        $bulanInfo = $row["bulan_1_12"] ?? $row["bulan"] ?? null;
        if (empty($bulanInfo)) $errors[] = "Bulan harus diisi.";
        elseif (!is_numeric($bulanInfo) || $bulanInfo < 1 || $bulanInfo > 12) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }

        if (empty($row["nama_kth"])) {
            $errors[] = "Nama KTH harus diisi.";
        }

        $kabupatenInfo = $row["nama_kabupaten"] ?? $row["kabupatenkota"] ?? null;
        $kecamatanInfo = $row["nama_kecamatan"] ?? $row["kecamatan"] ?? null;
        $desaInfo = $row["nama_desa"] ?? $row["desa"] ?? null;

        $regencyId = null;
        if (!$kabupatenInfo || trim((string)$kabupatenInfo) === "") {
            $errors[] = "Nama Kabupaten/Kota harus diisi.";
        } else {
            $regency = DB::table("m_regencies")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($kabupatenInfo)) . "%"])
                ->first();
            if (!$regency) {
                $errors[] = "Kabupaten/Kota \"" . $kabupatenInfo . "\" tidak ditemukan.";
            } else {
                $regencyId = $regency->id;
            }
        }

        $districtId = null;
        if (!$kecamatanInfo || trim((string)$kecamatanInfo) === "") {
            $errors[] = "Nama Kecamatan harus diisi.";
        } elseif ($regencyId) {
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

        if (!$desaInfo || trim((string)$desaInfo) === "") {
            $errors[] = "Nama Desa harus diisi.";
        } elseif ($districtId) {
            $village = DB::table("m_villages")
                ->where("district_id", $districtId)
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($desaInfo)) . "%"])
                ->exists();
            if (!$village) {
                $errors[] = "Desa/Kelurahan \"" . $desaInfo . "\" tidak ditemukan di kecamatan tersebut.";
            }
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

