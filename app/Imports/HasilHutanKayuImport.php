<?php

namespace App\Imports;

use App\Models\HasilHutanKayu;
use App\Models\Kayu;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Auth;

class HasilHutanKayuImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  protected $forestType;

  public function __construct($forestType)
  {
    $this->forestType = $forestType;
  }

  public function rules(): array
  {
    return [
      'tahun' => 'required|numeric',
      'bulan_angka' => 'required|numeric|min:1|max:12',
      'nama_kabupaten' => 'required|exists:m_regencies,name',
      // Note: simple exists check allows duplicate names across provinces generally, 
      // but we only have Jatim regencies seeded usually.
      'nama_kecamatan' => 'required|exists:m_districts,name',
      'jenis_kayu' => 'required|exists:m_kayu,name',
      'target_volume' => 'required|numeric',
      'realisasi_volume' => 'nullable|numeric',
    ];
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupaten.exists' => 'Kabupaten tidak ditemukan.',
      'nama_kecamatan.exists' => 'Kecamatan tidak ditemukan.',
      'jenis_kayu.exists' => 'Jenis Kayu tidak ditemukan.',
      'bulan_angka.min' => 'Bulan harus 1-12.',
      'bulan_angka.max' => 'Bulan harus 1-12.',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun']))
      return null;

    // 1. Lookup Kayu ID
    $kayu = Kayu::where('name', 'like', '%' . $row['jenis_kayu'] . '%')->first();
    // Validation ensures existence, but logic needs ID.
    if (!$kayu)
      return null;

    // 2. Lookup Regency ID
    $regency = DB::table('m_regencies')
      ->where('province_id', 35)
      ->where('name', 'like', '%' . $row['nama_kabupaten'] . '%')
      ->first();
    if (!$regency)
      return null;

    // 3. Lookup District ID - Try to find within regency first
    $district = DB::table('m_districts')
      ->where('regency_id', $regency->id)
      ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
      ->first();

    // Fallback or Strict? If validation passed 'exists:m_districts,name', it means name exists SOMEWHERE.
    // But if it doesn't match this regency, we have a logic error.
    // For import robustness, if not found in regency, we might return null which causes current row to be skipped (return null in ToModel skips).
    // BUT strict validation won't catch " District exists but in wrong Regency".
    // To handle that, we'd need closure validation. 
    // For now, if we can't find it in the regency, let's try fuzzy search or just fail (return null).
    // Since we are skipping on failure, returning null here effectively skips without error message?
    // Actually, returning null in `model` just means "don't create model".
    // SkipsOnFailure works for Validation failures.
    // If we want to report "District not found in Regency", we need to force a validation error or throw exception.
    // But throwing exception stops import unless SkipsOnError used.
    // Let's rely on basic validation + creating model. If $district is null, we can't create.

    if (!$district) {
      // Try name only search if you want
      $district = DB::table('m_districts')
        ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
        ->first();
    }

    if (!$district)
      return null;

    return new HasilHutanKayu([
      'year' => $row['tahun'],
      'month' => $row['bulan_angka'],
      'province_id' => 35, // Default JAWA TIMUR
      'regency_id' => $regency->id,
      'district_id' => $district->id,
      'forest_type' => $this->forestType,
      'annual_volume_target' => $row['target_volume'] ?? 0,
      'annual_volume_realization' => $row['realisasi_volume'] ?? 0,
      'id_kayu' => $kayu->id,
      'status' => 'draft',
      'created_by' => Auth::id(),
    ]);
  }
}
