<?php

namespace App\Imports;

use App\Models\Skps;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class SkpsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  public function rules(): array
  {
    return [
      'nama_kabupatenkota' => 'required|string',
      'nama_kecamatan' => 'required|string',
      'nama_kelompok' => 'required|string',
      'nama_skema_perhutanan_sosial' => 'required|string',
      'potensi_ha' => 'required|string',
      'luas_ps_ha' => 'required|string',
      'jumlah_kk' => 'required|string',
    ];
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupatenkota.required' => 'Nama Kabupaten/Kota harus diisi.',
      'nama_kecamatan.required' => 'Nama Kecamatan harus diisi.',
      'nama_kelompok.required' => 'Nama Kelompok harus diisi.',
      'nama_skema_perhutanan_sosial.required' => 'Nama Skema Perhutanan Sosial harus diisi.',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['nama_kabupatenkota']))
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

    // Look up skema perhutanan sosial by name
    $skema = DB::table('m_skema_perhutanan_sosial')
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_skema_perhutanan_sosial'])) . '%'])
      ->first();

    if (!$skema) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_skema_perhutanan_sosial',
        ["Skema Perhutanan Sosial '{$row['nama_skema_perhutanan_sosial']}' tidak ditemukan."]
      ));
      return null;
    }

    return new Skps([
      'province_id' => $regency->province_id,
      'regency_id' => $regency->id,
      'district_id' => $district->id,
      'id_skema_perhutanan_sosial' => $skema->id,
      'nama_kelompok' => $row['nama_kelompok'],
      'potential' => $row['potensi_ha'],
      'ps_area' => $row['luas_ps_ha'],
      'number_of_kk' => $row['jumlah_kk'],
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
