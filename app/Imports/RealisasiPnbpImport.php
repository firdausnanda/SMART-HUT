<?php

namespace App\Imports;

use App\Models\RealisasiPnbp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class RealisasiPnbpImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  public function rules(): array
  {
    return [
      'tahun' => 'required|numeric',
      'bulan_angka_1_12' => 'required|numeric|min:1|max:12',
      'nama_kabupatenkota' => 'required|string',
      'nama_pengelola_wisata' => 'required|string',
      'jenis_hasil_hutan' => 'required|string',
      'target_pnbp' => 'required|string',
      'realisasi_pnbp' => 'required|string',
    ];
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupatenkota.required' => 'Nama Kabupaten/Kota harus diisi.',
      'nama_pengelola_wisata.required' => 'Nama Pengelola Wisata harus diisi.',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun']))
      return null;

    // Look up regency by name
    $regency = DB::table('m_regencies')
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kabupatenkota'])) . '%'])
      ->first();

    if (!$regency) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_kabupatenkota',
        ["Kabupaten/Kota '{$row['nama_kabupatenkota']}' tidak ditemukan."]
      ));
      return null;
    }

    // Look up pengelola wisata by name
    $pengelolaWisata = DB::table('m_pengelola_wisata')
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_pengelola_wisata'])) . '%'])
      ->first();

    if (!$pengelolaWisata) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_pengelola_wisata',
        ["Pengelola Wisata '{$row['nama_pengelola_wisata']}' tidak ditemukan."]
      ));
      return null;
    }

    return new RealisasiPnbp([
      'year' => $row['tahun'],
      'month' => $row['bulan_angka_1_12'],
      'province_id' => $regency->province_id,
      'regency_id' => $regency->id,
      'id_pengelola_wisata' => $pengelolaWisata->id,
      'types_of_forest_products' => $row['jenis_hasil_hutan'],
      'pnbp_target' => $row['target_pnbp'],
      'pnbp_realization' => $row['realisasi_pnbp'],
      'status' => 'draft',
      'created_by' => Auth::id(),
    ]);
  }

  private $rowNumber = 1;

  public function getRowNumber()
  {
    return $this->rowNumber++;
  }
}
