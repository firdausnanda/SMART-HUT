<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class RhlTeknisImportValidator
{
    /**
     * @param array $row
     * @param int $rowNumber
     * @return bool|array
     */
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (empty($row["tahun"])) $errors[] = "Tahun harus diisi.";
        elseif (!is_numeric($row["tahun"])) $errors[] = "Tahun harus berupa angka.";

        if (empty($row["bulan_angka"])) $errors[] = "Bulan harus diisi.";
        elseif (!is_numeric($row["bulan_angka"]) || $row["bulan_angka"] < 1 || $row["bulan_angka"] > 12) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }

        if (empty($row["kabupaten"])) $errors[] = "Nama Kabupaten harus diisi.";
        if (empty($row["kecamatan"])) $errors[] = "Nama Kecamatan harus diisi.";
        if (empty($row["desa"])) $errors[] = "Nama Desa harus diisi.";
        
        if (!isset($row["target_tahunan_unit"]) || $row["target_tahunan_unit"] === "") {
            $errors[] = "Target Tahunan Unit harus diisi.";
        } elseif (!is_numeric($row["target_tahunan_unit"])) {
            $errors[] = "Target Tahunan Unit harus berupa angka.";
        }

        if (empty($row["sumber_dana"])) {
            $errors[] = "Sumber Dana harus diisi.";
        } else {
            $sumberDana = DB::table("m_sumber_dana")
                ->whereRaw("LOWER(name) = ?", [strtolower(trim($row["sumber_dana"]))])
                ->exists();
            if (!$sumberDana) {
                $errors[] = "Sumber Dana " . $row["sumber_dana"] . " tidak ditemukan di Master Sumber Dana.";
            }
        }

        if (empty($row["jenis_bangunan"])) $errors[] = "Jenis Bangunan harus diisi.";
        if (empty($row["jumlah_unit"])) $errors[] = "Jumlah Unit harus diisi.";

        if (!empty($row["jenis_bangunan"]) && !empty($row["jumlah_unit"])) {
            $types = array_map("trim", explode(",", $row["jenis_bangunan"]));
            $units = array_map("trim", explode(",", $row["jumlah_unit"]));
            
            if (count($types) !== count($units)) {
                $errors[] = "Jumlah item pada Jenis Bangunan dan Jumlah Unit tidak sama.";
            }
        }

        if (!empty($errors)) return $errors;

        $regency = DB::table("m_regencies")
            ->where("province_id", 35)
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["kabupaten"])) . "%"])
            ->first();
            
        if (!$regency) {
            return ["Kabupaten " . $row["kabupaten"] . " tidak ditemukan di Jawa Timur."];
        }

        $district = DB::table("m_districts")
            ->where("regency_id", $regency->id)
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["kecamatan"])) . "%"])
            ->first();
            
        if (!$district) {
            $district = DB::table("m_districts")
                ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["kecamatan"])) . "%"])
                ->first();
                
            if (!$district) {
                return ["Kecamatan " . $row["kecamatan"] . " tidak ditemukan."];
            }
        }
        
        $village = DB::table("m_villages")
            ->where("district_id", $district->id)
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower(trim($row["desa"])) . "%"])
            ->first();
            
        if (!$village) {
            return ["Desa " . $row["desa"] . " tidak valid/ditemukan pada kecamatan tersebut."];
        }

        return true;
    }
}

