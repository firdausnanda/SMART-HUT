<?php

namespace App\Imports;

use App\Models\RhlTeknis;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class RhlTeknisImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure
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
      'target_tahunan_unit' => 'required|numeric',
      'sumber_dana' => ['required', 'exists:m_sumber_dana,name'],
      'kabupaten' => 'required|string|exists:m_regencies,name',
      'kecamatan' => 'required|string|exists:m_districts,name',
      'desa' => 'required|string|exists:m_villages,name',
      'jenis_bangunan' => 'required|string',
      'jumlah_unit' => 'required|string',
    ];
  }

  public function customValidationMessages()
  {
    return [
      'sumber_dana.exists' => 'Sumber Dana tidak ditemukan di data referensi (Master Sumber Dana).',
      'kabupaten.exists' => 'Kabupaten tidak valid.',
      'kecamatan.exists' => 'Kecamatan tidak valid.',
      'desa.exists' => 'Desa tidak valid.',
    ];
  }

  public function onRow(Row $row)
  {
    $rowData = $row->toArray();

    // 1. Create Parent Record

    // Find Location IDs
    $regency = \App\Models\Regencies::where('name', 'like', '%' . trim($rowData['kabupaten']) . '%')->first();
    $district = \App\Models\Districts::where('name', 'like', '%' . trim($rowData['kecamatan']) . '%')
      ->where('regency_id', $regency?->id)
      ->first();
    $village = \App\Models\Villages::where('name', 'like', '%' . trim($rowData['desa']) . '%')
      ->where('district_id', $district?->id)
      ->first();

    $rhlTeknis = RhlTeknis::create([
      'year' => $rowData['tahun'],
      'month' => $rowData['bulan_angka'],
      'target_annual' => $rowData['target_tahunan_unit'],
      'fund_source' => $rowData['sumber_dana'] ?? 'other',
      'province_id' => 35, // JAWA TIMUR Default
      'regency_id' => $regency?->id,
      'district_id' => $district?->id,
      'village_id' => $village?->id,
      'status' => 'draft',
      'created_by' => Auth::id(),
    ]);

    // 2. Parse Comma-Separated Values
    $types = array_map('trim', explode(',', $rowData['jenis_bangunan']));
    $units = array_map('trim', explode(',', $rowData['jumlah_unit']));

    if (count($types) !== count($units)) {
      // Log error or skipped? For now we just skip the details if mismatch
      // In a better world we would fail the row validation.
      // But since we are inside onRow, we can't easily bubble up validation error unless we throw exception.
      return;
    }

    // 3. Create Details
    foreach ($types as $index => $typeName) {
      $bangunan = \App\Models\BangunanKta::where('name', $typeName)->first();

      if ($bangunan) {
        \App\Models\RhlTeknisDetail::create([
          'rhl_teknis_id' => $rhlTeknis->id,
          'bangunan_kta_id' => $bangunan->id,
          'unit_amount' => (int) $units[$index],
        ]);
      }
    }
  }
}
