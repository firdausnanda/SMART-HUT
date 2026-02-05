<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiEkonomiTemplateExport implements FromArray, WithHeadings, WithStyles
{
  public function array(): array
  {
    // Example data
    return [
      [
        'tahun' => date('Y'),
        'bulan_1_12' => 1,
        'nama_kabupaten' => 'Madiun',
        'nama_kecamatan' => 'Dolopo',
        'nama_kelompok' => 'KTH Maju Bersama',
        'komoditas' => 'Madu',
        'volume_produksi' => 100,
        'satuan' => 'Liter',
        'nilai_transaksi_rp' => 5000000,
      ]
    ];
  }

  public function headings(): array
  {
    return [
      'Tahun',
      'Bulan (1-12)',
      'Nama Kabupaten',
      'Nama Kecamatan',
      'Nama Kelompok',
      'Komoditas',
      'Volume Produksi',
      'Satuan',
      'Nilai Transaksi (Rp)',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
