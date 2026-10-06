<?php

namespace Modules\BinaUsaha\App\Exports;

use App\Models\Pbphh;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PbphhExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
  public const COLUMN_HEADINGS = [
    'no' => 'No', 'name' => 'Nama Industri', 'number' => 'Nomor Izin',
    'regency' => 'Kabupaten/Kota', 'district' => 'Kecamatan',
    'investment_value' => 'Nilai Investasi', 'number_of_workers' => 'Jumlah Tenaga Kerja',
    'present_condition' => 'Kondisi Saat Ini', 'jenis_produksi' => 'Jenis Produksi (Kapasitas)',
    'status' => 'Status', 'creator' => 'Diinput Oleh', 'created_at' => 'Tanggal Input',
  ];

  private array $filters;
  private array $columns;
  private int $rowNumber = 0;

  public function __construct(array $filters = [])
  {
    $this->filters = $filters;
    $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
  }

  public function query()
  {
    return Pbphh::query()
      ->with(['creator:id,name', 'regency', 'district', 'jenis_produksi'])
      ->when(($this->filters['status'] ?? 'final') !== 'all', fn($q) => $q->where('status', $this->filters['status'] ?? 'final'))
      ->when($this->filters['cdk_id'] ?? null, fn($q, $id) => $q->where('cdk_id', $id))
      ->when($this->filters['regency_id'] ?? null, fn($q, $id) => $q->where('regency_id', $id))
      ->when($this->filters['district_id'] ?? null, fn($q, $id) => $q->where('district_id', $id))
      ->orderBy('name', 'asc');
  }

  public function headings(): array
  {
    return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
  }

  public function map($row): array
  {
    $this->rowNumber++;

    $values = [
      'no' => $this->rowNumber,
      'name' => $row->name,
      'number' => $row->number,
      'regency' => $row->regency?->name ?? '-',
      'district' => $row->district?->name ?? '-',
      'investment_value' => number_format($row->investment_value, 0, ',', '.'),
      'number_of_workers' => $row->number_of_workers,
      'present_condition' => $row->present_condition ? 'Aktif' : 'Tidak Aktif',
      'jenis_produksi' => $row->jenis_produksi->map(function ($jp) {
        $capacity = is_numeric($jp->pivot->kapasitas_ijin) ? floatval($jp->pivot->kapasitas_ijin) : 0;
        return $jp->name . ' (' . $capacity . ' m³)';
      })->join(', '),
      'status' => ucfirst($row->status),
      'creator' => $row->creator?->name ?? 'Unknown',
      'created_at' => $row->created_at?->format('d-m-Y H:i') ?? '-',
    ];

    return array_map(fn($column) => $values[$column], $this->columns);
  }

  public function title(): string
  {
    return 'Data PBPHH';
  }
}
