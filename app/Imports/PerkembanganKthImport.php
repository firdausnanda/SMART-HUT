<?php

namespace App\Imports;

use App\Models\PerkembanganKth;
use App\Models\Regencies;
use App\Models\Districts;
use App\Models\Villages;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;

class PerkembanganKthImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures, Importable;

  public function model(array $row)
  {
    // Find location IDs by name
    $regency = Regencies::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['kabupatenkota'])) . '%'])->first();
    $district = Districts::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['kecamatan'])) . '%'])
      ->when($regency, fn($q) => $q->where('regency_id', $regency->id))
      ->first();
    $village = Villages::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['desa'])) . '%'])
      ->when($district, fn($q) => $q->where('district_id', $district->id))
      ->first();

    return new PerkembanganKth([
      'year' => $row['tahun'],
      'month' => $row['bulan_112'] ?? $row['bulan'],
      'province_id' => 35, // Jawa Timur
      'regency_id' => $regency?->id,
      'district_id' => $district?->id,
      'village_id' => $village?->id,
      'nama_kth' => $row['nama_kth'],
      'nomor_register' => $row['nomor_register'] ?? null,
      'kelas_kelembagaan' => strtolower(trim($row['kelas_kelembagaan_pemulamadyautama'] ?? $row['kelas_kelembagaan'])),
      'jumlah_anggota' => $row['jumlah_anggota'] ?? 0,
      'luas_kelola' => $row['luas_kelola_ha'] ?? $row['luas_kelola'] ?? 0,
      'potensi_kawasan' => $row['potensi_kawasan'] ?? null,
      'status' => 'draft',
    ]);
  }

  public function rules(): array
  {
    return [
      'tahun' => 'required|integer',
      'nama_kth' => 'required|string',
    ];
  }
}
