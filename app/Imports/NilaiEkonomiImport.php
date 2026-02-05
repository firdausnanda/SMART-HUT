<?php

namespace App\Imports;

use App\Models\NilaiEkonomi;
use App\Models\Commodity;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Auth;

class NilaiEkonomiImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
  use SkipsFailures;

  public function rules(): array
  {
    return [
      'tahun' => 'required|numeric',
      'bulan_1_12' => 'required|numeric|min:1|max:12',
      'nama_kabupaten' => 'required|string',
      'nama_kecamatan' => 'required|string',
      'nama_kelompok' => 'required|string',
      'komoditas' => 'required|string',
      'volume_produksi' => 'required|numeric',
      'satuan' => 'required|string',
      'nilai_transaksi_rp' => 'required|numeric',
    ];
  }

  public function model(array $row)
  {
    if (!isset($row['tahun'])) {
      return null;
    }

    $kabupatenInfo = $row['nama_kabupaten'] ?? null;
    $kecamatanInfo = $row['nama_kecamatan'] ?? null;
    $bulanInfo = $row['bulan_1_12'] ?? null;

    // 1. Lookup Location IDs
    $regency = DB::table('m_regencies')
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($kabupatenInfo)) . '%'])
      ->first();

    if (!$regency) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_kabupaten',
        ["Kabupaten/Kota '{$kabupatenInfo}' tidak ditemukan."]
      ));
      return null;
    }

    $district = DB::table('m_districts')
      ->where('regency_id', $regency->id)
      ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($kecamatanInfo)) . '%'])
      ->first();

    if (!$district) {
      $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
        $this->getRowNumber(),
        'nama_kecamatan',
        ["Kecamatan '{$kecamatanInfo}' tidak ditemukan di {$regency->name}."]
      ));
      return null;
    }

    // 2. Find or Create Parent Record
    $transaction = NilaiEkonomi::firstOrCreate([
      'year' => $row['tahun'],
      'month' => $bulanInfo,
      'nama_kelompok' => $row['nama_kelompok'],
      'province_id' => 35,
      'regency_id' => $regency->id,
      'district_id' => $district->id,
    ], [
      'status' => 'draft',
      'created_by' => Auth::id(),
      'total_transaction_value' => 0,
    ]);

    // 3. Find or Create Commodity
    $commodityName = trim($row['komoditas']);
    // For Nilai Ekonomi, we want commodities marked for it, or we create them and mark them
    $commodity = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')->firstOrCreate(
      ['name' => $commodityName],
      ['is_nilai_transaksi_ekonomi' => true]
    );

    // 4. Create Detail
    $nilai = $row['nilai_transaksi_rp'] ?? 0;

    $transaction->details()->create([
      'commodity_id' => $commodity->id,
      'production_volume' => $row['volume_produksi'],
      'satuan' => $row['satuan'],
      'transaction_value' => $nilai,
    ]);

    // 5. Update Total on Parent
    $transaction->increment('total_transaction_value', $nilai);

    return $transaction;
  }

  private $rowNumber = 1;

  public function getRowNumber()
  {
    return $this->rowNumber++;
  }
}
