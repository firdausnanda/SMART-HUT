<?php

namespace App\Imports;

use App\Models\IndustriBerizin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class IndustriBerizinImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  public function rules(): array
  {
    return [
      'tahun' => 'required|numeric',
      'bulan_angka_1_12' => 'required|numeric|min:1|max:12',
      'nama_kabupatenkota' => 'required|string',
      'nama_kecamatan' => 'required|string',
      'phhkpbhh' => 'required|string',
      'phhbkpbphh' => 'required|string',
      'nama_jenis_produksi' => 'required|string',
    ];
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupatenkota.required' => 'Nama Kabupaten/Kota harus diisi.',
      'nama_kecamatan.required' => 'Nama Kecamatan harus diisi.',
      'nama_jenis_produksi.required' => 'Nama Jenis Produksi harus diisi.',
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

    // Look up district by name within regency
    $district = DB::table('m_districts')
      ->where('regency_id', $regency->id)
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
      ->first();

    if (!$district) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_kecamatan',
        ["Kecamatan '{$row['nama_kecamatan']}' tidak ditemukan di {$regency->name}."]
      ));
      return null;
    }

    // Look up jenis produksi by name
    $jenisProduksi = DB::table('m_jenis_produksi')
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_jenis_produksi'])) . '%'])
      ->first();

    if (!$jenisProduksi) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_jenis_produksi',
        ["Jenis Produksi '{$row['nama_jenis_produksi']}' tidak ditemukan."]
      ));
      return null;
    }

    return new IndustriBerizin([
      'year' => $row['tahun'],
      'month' => $row['bulan_angka_1_12'],
      'province_id' => $regency->province_id,
      'regency_id' => $regency->id,
      'district_id' => $district->id,
      'phhk_pbhh' => $row['phhkpbhh'],
      'phhbk_pbphh' => $row['phhbkpbphh'],
      'id_jenis_produksi' => $jenisProduksi->id,
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
