<?php

namespace Modules\Perlindungan\App\Exports;

use App\Models\KebakaranHutan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class KebakaranHutanExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
  public const COLUMN_HEADINGS = [
    'no' => 'No', 'year' => 'Tahun', 'month' => 'Bulan',
    'regency' => 'Kabupaten/Kota', 'district' => 'Kecamatan', 'village' => 'Desa',
    'pengelola_wisata' => 'Pengelola Wisata', 'area_function' => 'Fungsi Kawasan',
    'number_of_fires' => 'Jumlah Kejadian', 'fire_area' => 'Luas Kebakaran (Ha)',
    'status' => 'Status', 'creator' => 'Diinput Oleh', 'created_at' => 'Tanggal Input',
  ];

  private $year;
  private array $filters;
  private array $columns;
  private int $rowNumber = 0;

  public function __construct($year = null, array $filters = [])
  {
    $this->year = $year;
    $this->filters = $filters;
    $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
  }

  public function query()
  {
    return KebakaranHutan::query()
      ->with(['creator:id,name', 'regency:id,name', 'district:id,name', 'village:id,name', 'pengelolaWisata:id,name'])
      ->when(($this->filters['status'] ?? 'final') !== 'all', fn($q) => $q->where('status', $this->filters['status'] ?? 'final'))
      ->when($this->year, fn($q) => $q->where('year', $this->year))
      ->when($this->filters['month'] ?? null, fn($q, $value) => $q->where('month', $value))
      ->when($this->filters['cdk_id'] ?? null, fn($q, $value) => $q->where('cdk_id', $value))
      ->when($this->filters['regency_id'] ?? null, fn($q, $value) => $q->where('regency_id', $value))
      ->when($this->filters['district_id'] ?? null, fn($q, $value) => $q->where('district_id', $value))
      ->when($this->filters['pengelola_wisata_id'] ?? null, fn($q, $value) => $q->where('id_pengelola_wisata', $value))
      ->orderBy('year', 'desc')
      ->orderBy('month', 'asc');
  }

  public function headings(): array
  {
    return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
  }

  public function map($row): array
  {
    $this->rowNumber++;
    $monthNames = [
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

    $values = [
      'no' => $this->rowNumber,
      'year' => $row->year,
      'month' => $monthNames[$row->month] ?? $row->month,
      'regency' => $row->regency?->name ?? '-',
      'district' => $row->district?->name ?? '-',
      'village' => $row->village?->name ?? '-',
      'pengelola_wisata' => $row->pengelolaWisata?->name ?? '-',
      'area_function' => $row->area_function,
      'number_of_fires' => $row->number_of_fires,
      'fire_area' => $row->fire_area,
      'status' => ucfirst($row->status),
      'creator' => $row->creator?->name ?? 'Unknown',
      'created_at' => $row->created_at?->format('d-m-Y H:i') ?? '-',
    ];

    return array_map(fn($column) => $values[$column], $this->columns);
  }

  public function title(): string
  {
    return 'Kebakaran Hutan ' . ($this->year ?? 'Semua Tahun');
  }
}
