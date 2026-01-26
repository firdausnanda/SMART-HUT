<?php

namespace App\Imports;

use App\Models\ReboisasiPS;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ReboisasiPsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  public function prepareForValidation($data, $index)
  {
    if (isset($data['sumber_dana'])) {
      $data['sumber_dana'] = strtolower(trim($data['sumber_dana']));
    }

    return $data;
  }

  public function rules(): array
  {
    return [
      'tahun' => 'required|numeric',
      'bulan_angka' => 'required|numeric|min:1|max:12',
      'nama_kabupaten' => 'required|exists:m_regencies,name',
      'nama_kecamatan' => 'required|exists:m_districts,name',
      'target_tahunan_ha' => 'required|numeric',
      'realisasi_ha' => 'required|numeric',
      'sumber_dana' => ['required', 'exists:m_sumber_dana,name'],
    ];
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupaten.exists' => 'Kabupaten tidak ditemukan.',
      'nama_kecamatan.exists' => 'Kecamatan tidak ditemukan.',
      'sumber_dana.exists' => 'Sumber Dana tidak ditemukan di data referensi (Master Sumber Dana).',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun']))
      return null;

    $regency = DB::table('m_regencies')->where('province_id', 35)->where('name', 'like', '%' . $row['nama_kabupaten'] . '%')->first();
    if (!$regency)
      return null;

    $district = DB::table('m_districts')->where('regency_id', $regency->id)->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')->first();
    if (!$district)
      $district = DB::table('m_districts')->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')->first();
    if (!$district)
      return null;

    $village = null;
    if (!empty($row['nama_desa'])) {
      $village = DB::table('m_villages')->where('district_id', $district->id)->where('name', 'like', '%' . $row['nama_desa'] . '%')->first();
    }

    return new ReboisasiPS([
      'year' => $row['tahun'],
      'month' => $row['bulan_angka'],
      'province_id' => 35,
      'regency_id' => $regency->id,
      'district_id' => $district->id,
      'village_id' => $village?->id,
      'target_annual' => $row['target_tahunan_ha'] ?? 0,
      'realization' => $row['realisasi_ha'] ?? 0,
      'fund_source' => $row['sumber_dana'] ?? 'other',
      'status' => 'draft',
      'created_by' => Auth::id(),
    ]);
  }
}
