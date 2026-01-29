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
    $rules = [
      'tahun' => 'required|numeric',
      'bulan_angka' => 'required|numeric|min:1|max:12',
      'nama_kabupaten' => 'required|exists:m_regencies,name',
    ];

    if ($this->forestType !== 'Hutan Negara') {
      $rules['nama_kecamatan'] = 'required|exists:m_districts,name';
    }
    // For Hutan Negara, pengelola is optional in import usually because name might not match or be new, 
    // but better if we require it or allow creation. Current logic allows creation. 
    // Let's keep it implicitly optional in rules but checked in model if provided.

    return $rules;
  }

  public function customValidationMessages()
  {
    return [
      'nama_kabupaten.exists' => 'Kabupaten tidak ditemukan.',
      'nama_kecamatan.exists' => 'Kecamatan tidak ditemukan.',
      'nama_kecamatan.required' => 'Kecamatan wajib diisi untuk jenis hutan ini.',
      'bulan_angka.min' => 'Bulan harus 1-12.',
      'bulan_angka.max' => 'Bulan harus 1-12.',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun']))
      return null;

    // Resolve Location
    $regency = DB::table('m_regencies')
      ->where('province_id', 35)
      ->where('name', 'like', '%' . $row['nama_kabupaten'] . '%')
      ->first();
    if (!$regency)
      return null;

    $district = null;
    $districtId = null;

    if ($this->forestType !== 'Hutan Negara') {
      if (empty($row['nama_kecamatan'])) {
        return null; // Should be caught by validation, but safe check
      }
      $district = DB::table('m_districts')
        ->where('regency_id', $regency->id)
        ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
        ->first();

      if (!$district) {
        $district = DB::table('m_districts')
          ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
          ->first();
      }
      if (!$district)
        return null;
      $districtId = $district->id;
    }

    // Resolve Pengelola Hutan (Optional/Nullable in DB but good to have)
    $pengelolaHutanId = null;
    if ($this->forestType === 'Hutan Negara' && !empty($row['nama_pengelola_hutan'])) {
      $pengelola = \App\Models\PengelolaHutan::firstOrCreate(
        ['name' => $row['nama_pengelola_hutan']]
      );
      $pengelolaHutanId = $pengelola->id;
    }

    // Build Details array from dynamic columns
    $detailsData = [];
    $allKayu = Kayu::all();

    foreach ($allKayu as $kayu) {
      // Excel heading logic converts "Kayu Jati - Target" to "kayu_jati_target"
      // or similar depending on Maatwebsite slugging.
      // Usually simple slug: 'Jati - Target' -> 'jati_target'
      // We rely on the template generating "Name - Target"

      $slugName = \Illuminate\Support\Str::slug($kayu->name, '_');
      $targetKey = $slugName . '_target';
      $realizationKey = $slugName . '_realisasi';

      // Check if keys exist in row (even if null or 0)
      // Note: HeadingRow slugifies headers.
      if (array_key_exists($targetKey, $row)) {
        $target = $row[$targetKey];
        $realization = $row[$realizationKey] ?? 0;

        // Only add if there is a target or realization value
        if ($target > 0 || $realization > 0) {
          $detailsData[] = [
            'kayu_id' => $kayu->id,
            'volume_target' => $target ?? 0,
            'volume_realization' => $realization ?? 0,
          ];
        }
      }
    }

    if (empty($detailsData)) {
      return null;
    }

    return DB::transaction(function () use ($row, $regency, $districtId, $pengelolaHutanId, $detailsData) {
      $parent = HasilHutanKayu::create([
        'year' => $row['tahun'],
        'month' => $row['bulan_angka'],
        'province_id' => 35,
        'regency_id' => $regency->id,
        'district_id' => $districtId,
        'pengelola_hutan_id' => $pengelolaHutanId,
        'forest_type' => $this->forestType,
        'status' => 'draft',
        'created_by' => Auth::id(),
      ]);

      foreach ($detailsData as $detail) {
        $parent->details()->create($detail);
      }

      return $parent;
    });
  }
}
