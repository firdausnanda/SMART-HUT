<?php

namespace App\Exports;

use App\Models\PerkembanganKth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PerkembanganKthExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
  protected $year;

  public function __construct($year = null)
  {
    $this->year = $year;
  }

  public function collection()
  {
    return PerkembanganKth::query()
      ->with(['regency_rel', 'district_rel', 'village_rel'])
      ->when($this->year, fn($q) => $q->where('year', $this->year))
      ->where('status', 'final')
      ->get();
  }

  public function headings(): array
  {
    return [
      'No',
      'Tahun',
      'Bulan',
      'Kabupaten/Kota',
      'Kecamatan',
      'Desa',
      'Nama KTH',
      'Nomor Register',
      'Kelas Kelembagaan',
      'Jumlah Anggota',
      'Luas Kelola (Ha)',
      'Potensi Kawasan',
      'Status',
    ];
  }

  public function map($row): array
  {
    static $no = 0;
    $no++;

    $months = [
      1 => 'Januari',
      2 => 'Februari',
      3 => 'Maret',
      4 => 'April',
      5 => 'Mei',
      6 => 'Juni',
      7 => 'Juli',
      8 => 'Agustus',
      9 => 'September',
      10 => 'Oktober',
      11 => 'November',
      12 => 'Desember'
    ];

    $kelasLabels = [
      'pemula' => 'Pemula',
      'madya' => 'Madya',
      'utama' => 'Utama',
    ];

    return [
      $no,
      $row->year,
      $months[$row->month] ?? $row->month,
      $row->regency_rel?->name ?? '-',
      $row->district_rel?->name ?? '-',
      $row->village_rel?->name ?? '-',
      $row->nama_kth,
      $row->nomor_register ?? '-',
      $kelasLabels[$row->kelas_kelembagaan] ?? $row->kelas_kelembagaan,
      $row->jumlah_anggota,
      $row->luas_kelola,
      $row->potensi_kawasan ?? '-',
      ucfirst($row->status),
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
