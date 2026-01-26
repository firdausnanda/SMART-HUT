<?php

namespace App\Imports;

use App\Models\RhlTeknis;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class RhlTeknisImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
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
      'target_tahunan_ha' => 'required|numeric',
      'sumber_dana' => ['required', 'exists:m_sumber_dana,name'],
    ];
  }

  public function customValidationMessages()
  {
    return [
      'sumber_dana.exists' => 'Sumber Dana tidak ditemukan di data referensi (Master Sumber Dana).',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun']))
      return null;

    return new RhlTeknis([
      'year' => $row['tahun'],
      'month' => $row['bulan_angka'],
      'target_annual' => $row['target_tahunan_ha'] ?? 0,
      'fund_source' => $row['sumber_dana'] ?? 'other',
      'status' => 'draft',
      'created_by' => Auth::id(),
    ]);
  }
}
