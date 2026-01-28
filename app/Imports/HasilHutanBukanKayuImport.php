<?php

namespace App\Imports;

use App\Models\HasilHutanBukanKayu;
use App\Models\BukanKayu;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Auth;

class HasilHutanBukanKayuImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  protected $forestType;
  protected $commodities;

  public function __construct($forestType)
  {
    $this->forestType = $forestType;
    $this->commodities = \App\Models\Commodity::all();
  }

  public function rules(): array
  {
    return [
      'tahun' => 'required|numeric',
      'bulan_angka' => 'required|numeric|min:1|max:12',
      'nama_kabupaten' => 'required|exists:m_regencies,name',
      'nama_kecamatan' => 'required|exists:m_districts,name',
    ];
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupaten.exists' => 'Kabupaten tidak ditemukan.',
      'nama_kecamatan.exists' => 'Kecamatan tidak ditemukan.',
      'bulan_angka.min' => 'Bulan harus 1-12.',
      'bulan_angka.max' => 'Bulan harus 1-12.',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun']))
      return null;

    // 1. Lookup Regency ID
    $regency = DB::table('m_regencies')
      ->where('province_id', 35)
      ->where('name', 'like', '%' . $row['nama_kabupaten'] . '%')
      ->first();
    if (!$regency)
      return null;

    // 2. Lookup District ID
    $district = DB::table('m_districts')
      ->where('regency_id', $regency->id) // Strict check
      ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
      ->first();

    if (!$district) {
      $district = DB::table('m_districts')
        ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
        ->first();
    }

    if (!$district)
      return null;

    // Create Parent
    $hhbk = HasilHutanBukanKayu::create([
      'year' => $row['tahun'],
      'month' => $row['bulan_angka'],
      'province_id' => 35, // Default JAWA TIMUR
      'regency_id' => $regency->id,
      'district_id' => $district->id,
      'forest_type' => $this->forestType,
      'status' => 'draft',
      'created_by' => Auth::id(),
    ]);

    // 3. Loop through commodities and check for data in row
    foreach ($this->commodities as $commodity) {
      // Laravel Excel typically converts to snake_case.
      // "Madu - Target" -> "madu_target"
      // "Madu - Realisasi" -> "madu_realisasi"
      // Handle potential special characters in commodity name if needed, but assuming simple logic for now.
      $slug = \Illuminate\Support\Str::slug($commodity->name, '_');
      $targetKey = $slug . '_target';
      $realizationKey = $slug . '_realisasi';

      $targetVolume = $row[$targetKey] ?? 0;
      $realizationVolume = $row[$realizationKey] ?? 0;

      // Only add detail if there is non-zero data
      if ($targetVolume > 0 || $realizationVolume > 0) {
        $hhbk->details()->create([
          'commodity_id' => $commodity->id,
          'volume' => $targetVolume,
          'annual_volume_realization' => $realizationVolume,
          'unit' => 'Kg',
        ]);
      }
    }

    return $hhbk;
  }
}
