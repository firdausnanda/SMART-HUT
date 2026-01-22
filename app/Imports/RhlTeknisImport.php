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
      'sumber_dana' => ['required', \Illuminate\Validation\Rule::in(['apbn', 'apbd provinsi', 'apbd kabupaten/kota', 'bums', 'csr', 'bpdlh', 'lainnya'])],
    ];
  }

  public function customValidationMessages()
  {
    return [
      'sumber_dana.in' => 'Sumber Dana harus: apbn, apbd provinsi, apbd kabupaten/kota, bums, csr, bpdlh, atau lainnya.',
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
