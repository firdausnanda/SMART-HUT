<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class HasilHutanKayuImportValidator
{
    protected $forestType;

    public function __construct($forestType)
    {
        $this->forestType = $forestType;
    }

    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (!isset($row["tahun"]) || trim((string)$row["tahun"]) === "") $errors[] = "Tahun harus diisi.";
        elseif (!is_numeric($row["tahun"])) $errors[] = "Tahun harus berupa angka.";

        if (!isset($row["bulan_angka"]) || trim((string)$row["bulan_angka"]) === "") $errors[] = "Bulan harus diisi.";
        elseif (!is_numeric($row["bulan_angka"]) || $row["bulan_angka"] < 1 || $row["bulan_angka"] > 12) {
            $errors[] = "Bulan harus berupa angka 1-12.";
        }

        if (!isset($row["nama_kabupaten"]) || trim((string)$row["nama_kabupaten"]) === "") {
            $errors[] = "Nama Kabupaten harus diisi.";
        } else {
            $regency = DB::table("m_regencies")
                ->where("province_id", 35)
                ->where("name", "like", "%" . $row["nama_kabupaten"] . "%")
                ->exists();
            if (!$regency) $errors[] = "Kabupaten tidak ditemukan.";
        }

        if (!isset($row["total_target_m3"]) || $row["total_target_m3"] === "") {
            $errors[] = "Total Target (m3) harus diisi.";
        } elseif (!is_numeric($row["total_target_m3"]) || $row["total_target_m3"] < 0) {
            $errors[] = "Total Target (m3) harus berupa angka dan minimal 0.";
        }

        if ($this->forestType === "Hutan Rakyat") {
            if (!!isset($row["nama_kecamatan"]) || trim((string)$row["nama_kecamatan"]) === "") {
                $district = DB::table("m_districts")
                    ->where("name", "like", "%" . $row["nama_kecamatan"] . "%")
                    ->exists();
                if (!$district) $errors[] = "Kecamatan tidak ditemukan.";
            }
        }

        if ($this->forestType === "Perhutanan Sosial") {
            if (!isset($row["nama_pengelola_wisata"]) || trim((string)$row["nama_pengelola_wisata"]) === "") {
                $errors[] = "Pengelola Wisata wajib diisi untuk jenis hutan ini.";
            } else {
                $pengelolaWisata = DB::table("m_pengelola_wisata")
                    ->where("name", "like", "%" . $row["nama_pengelola_wisata"] . "%")
                    ->exists();
                if (!$pengelolaWisata) $errors[] = "Pengelola Wisata tidak ditemukan.";
            }
        }

        if (!empty($errors)) return $errors;
        return true;
    }
}


