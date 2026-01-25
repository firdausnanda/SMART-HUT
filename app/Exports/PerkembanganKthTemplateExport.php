<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PerkembanganKthTemplateExport implements FromArray, WithHeadings, WithStyles
{
  public function array(): array
  {
    return [
      [
        2026,
        1,
        'TRENGGALEK',
        'BENDUNGAN',
        'MASARAN',
        'KTH Makmur Sejahtera',
        'REG-001',
        'pemula',
        25,
        50.5,
        'Hutan jati, potensi kayu'
      ],
    ];
  }

  public function headings(): array
  {
    return [
      'Tahun',
      'Bulan (1-12)',
      'Kabupaten/Kota',
      'Kecamatan',
      'Desa',
      'Nama KTH',
      'Nomor Register',
      'Kelas Kelembagaan (pemula/madya/utama)',
      'Jumlah Anggota',
      'Luas Kelola (Ha)',
      'Potensi Kawasan',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
