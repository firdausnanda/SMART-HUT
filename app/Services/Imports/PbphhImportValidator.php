<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class PbphhImportValidator
{
    public function __invoke(array $row, int $rowNumber)
    {
        $errors = [];

        if (!isset($row["nama_industri"]) || trim((string)$row["nama_industri"]) === "") $errors[] = "Nama Industri harus diisi.";
        if (!isset($row["nomor_izin"]) || trim((string)$row["nomor_izin"]) === "") $errors[] = "Nomor Izin harus diisi.";
        
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

        if (!isset($row["nilai_investasi"]) || $row["nilai_investasi"] === "") {
            $errors[] = "Nilai Investasi harus diisi.";
        } elseif (!is_numeric($row["nilai_investasi"]) || $row["nilai_investasi"] < 0) {
            $errors[] = "Nilai Investasi harus berupa angka dan minimal 0.";
        }

        if (!isset($row["jumlah_tenaga_kerja"]) || $row["jumlah_tenaga_kerja"] === "") {
            $errors[] = "Jumlah Tenaga Kerja harus diisi.";
        } elseif (!is_numeric($row["jumlah_tenaga_kerja"]) || $row["jumlah_tenaga_kerja"] < 0) {
            $errors[] = "Jumlah Tenaga Kerja harus berupa angka dan minimal 0.";
        }

        if (!isset($row["kondisi_saat_ini"]) || $row["kondisi_saat_ini"] === "") {
            $errors[] = "Kondisi Saat Ini harus diisi.";
        } else {
            $condition = strtolower(trim($row["kondisi_saat_ini"]));
            if (!in_array($condition, ["aktif", "tidak aktif", "1", "0", "true", "false"])) {
                $errors[] = "Kondisi Saat Ini tidak valid.";
            }
        }

        if (!isset($row["jenis_produksi_kapasitas"]) || trim((string)$row["jenis_produksi_kapasitas"]) === "") {
            $errors[] = "Jenis Produksi harus diisi.";
        } else {
            $rawJenis = $row["jenis_produksi_kapasitas"];
            $items = array_map("trim", explode(",", $rawJenis));
            $foundAny = false;
            
            foreach ($items as $item) {
                if (preg_match("/^(.+?)\s*\((.+?)\)$/", $item, $matches)) {
                    $name = trim($matches[1]);
                } else {
                    $name = $item;
                }
                
                $jenisProduksi = DB::table("m_jenis_produksi")
                    ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($name) . "%"])
                    ->exists();
                
                if ($jenisProduksi) {
                    $foundAny = true;
                } else {
                    $errors[] = "Jenis Produksi \"" . $name . "\" tidak ditemukan.";
                }
            }
            
            if (!$foundAny && empty($errors)) {
                $errors[] = "Tidak ada Jenis Produksi yang valid.";
            }
        }

        if (!empty($errors)) return $errors;
        return true;
    }
}


