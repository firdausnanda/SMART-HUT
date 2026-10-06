<?php

namespace Modules\BinaUsaha\App\Exports;

use App\Models\HasilHutanKayu;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HasilHutanKayuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
  public const COLUMN_HEADINGS = [
    'id' => 'ID', 'year' => 'Tahun', 'month' => 'Bulan', 'province' => 'Provinsi',
    'regency' => 'Kabupaten', 'location' => 'Kecamatan / Pengelola',
    'details' => 'Detail Kayu (Jenis)', 'volume_target' => 'Total Target (m3)',
    'volume_realization' => 'Total Realisasi (m3)', 'status' => 'Status',
    'creator' => 'Diinput Oleh', 'created_at' => 'Tanggal Input',
  ];

  protected $forestType;
  protected $year;
  protected array $filters;
  private array $columns;

  public function __construct($forestType, $year = null, array $filters = [])
  {
    $this->forestType = $forestType;
    $this->year = $year;
    $this->filters = $filters;
    $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
  }

  public function query()
  {
    return HasilHutanKayu::query()
      ->with(['regency', 'district', 'pengelolaHutan', 'pengelolaWisata', 'details.kayu', 'creator:id,name'])
      ->where('forest_type', $this->forestType)
      ->when($this->year, function ($q) {
        return $q->where('year', $this->year);
      })
      ->when($this->filters['month'] ?? null, fn($q, $month) => $q->where('month', $month))
      ->when(($this->filters['status'] ?? 'all') !== 'all', fn($q) => $q->where('status', $this->filters['status']))
      ->when($this->filters['cdk_id'] ?? null, fn($q, $id) => $q->where('cdk_id', $id))
      ->when($this->filters['regency_id'] ?? null, fn($q, $id) => $q->where('regency_id', $id))
      ->when($this->filters['district_id'] ?? null, fn($q, $id) => $q->where('district_id', $id));
  }

  public function headings(): array
  {
    return array_map(fn($column) => self::headingsFor($this->forestType)[$column], $this->columns);
  }

  public static function headingsFor(string $forestType): array
  {
    $location = match ($forestType) {
      'Hutan Negara' => 'Pengelola Hutan',
      'Perhutanan Sosial' => 'Pengelola Wisata',
      default => 'Kecamatan',
    };

    return array_replace(self::COLUMN_HEADINGS, ['location' => $location]);
  }

  public function map($row): array
  {
    $detailsString = $row->details->map(function ($detail) {
      return ($detail->kayu->name ?? '-') . ' (R:' . floatval($detail->volume_realization) . ')';
    })->implode(', ');

    $locationName = '-';
    if ($this->forestType === 'Hutan Negara') {
      $locationName = $row->pengelolaHutan->name ?? '-';
    } elseif ($this->forestType === 'Perhutanan Sosial') {
      $locationName = $row->pengelolaWisata->name ?? '-';
    } else {
      $locationName = $row->district->name ?? '-';
    }

    $values = [
      'id' => $row->id,
      'year' => $row->year,
      'month' => date('F', mktime(0, 0, 0, $row->month, 10)),
      'province' => 'JAWA TIMUR',
      'regency' => $row->regency?->name ?? '-',
      'location' => $locationName,
      'details' => $detailsString,
      'volume_target' => $row->volume_target,
      'volume_realization' => $row->details->sum('volume_realization'),
      'status' => $row->status,
      'creator' => $row->creator?->name ?? 'Unknown',
      'created_at' => $row->created_at?->format('d-m-Y H:i') ?? '-',
    ];

    return array_map(fn($column) => $values[$column], $this->columns);
  }
}
