<?php

namespace App\Exports;

use App\Models\NilaiEkonomi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiEkonomiExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
  protected $year;

  public function __construct($year = null)
  {
    $this->year = $year;
  }

  public function collection()
  {
    $records = NilaiEkonomi::query()
      ->with(['regency', 'district', 'details.commodity'])
      ->when($this->year, fn($q) => $q->where('year', $this->year))
      ->where('status', 'final')
      ->get();

    $rows = collect();
    foreach ($records as $record) {
      foreach ($record->details as $detail) {
        $detail->parent = $record;
        $rows->push($detail);
      }
    }

    return $rows;
  }

  public function headings(): array
  {
    return [
      'No',
      'Tahun',
      'Bulan',
      'Kabupaten/Kota',
      'Kecamatan',
      'Nama Kelompok',
      'Komoditas',
      'Volume Produksi',
      'Satuan',
      'Nilai Transaksi (Rp)',
      'Status',
    ];
  }

  public function map($detail): array
  {
    static $no = 0;
    $no++;

    $row = $detail->parent;
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

    return [
      $no,
      $row->year,
      $months[$row->month] ?? $row->month,
      $row->regency?->name ?? '-',
      $row->district?->name ?? '-',
      $row->nama_kelompok,
      $detail->commodity?->name ?? '-',
      $detail->production_volume,
      $detail->satuan,
      $detail->transaction_value,
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
