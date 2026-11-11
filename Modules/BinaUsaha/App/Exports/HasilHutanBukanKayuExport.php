<?php

namespace Modules\BinaUsaha\App\Exports;

use App\Models\HasilHutanBukanKayu;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HasilHutanBukanKayuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
  public const COLUMN_HEADINGS = [
    'id' => 'ID', 'year' => 'Tahun', 'month' => 'Bulan', 'province' => 'Provinsi',
    'regency' => 'Kabupaten/Kota', 'location' => 'Kecamatan / Pengelola',
    'commodity' => 'Komoditas', 'realization' => 'Realisasi', 'unit' => 'Satuan',
    'volume_target' => 'Total Target', 'status' => 'Status',
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
    return HasilHutanBukanKayu::query()
      ->with(['regency', 'district', 'pengelolaHutan', 'pengelolaWisata', 'details.bukanKayu', 'creator:id,name'])
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
    return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
  }

  /**
   * Satu baris per komoditas. Data tanpa komoditas tetap menghasilkan satu baris.
   * Total Target adalah nilai per laporan, jadi hanya diisi di baris pertama
   * supaya penjumlahan kolom di Excel tidak terhitung ganda.
   */
  public function map($row): array
  {
    $details = $row->details->isEmpty() ? collect([null]) : $row->details->values();

    return $details->map(function ($detail, $index) use ($row) {
      $values = $this->baseValues($row) + [
        'commodity' => $detail ? ($detail->bukanKayu->name ?? '?') : '-',
        'realization' => $detail ? (float) ($detail->annual_volume_realization ?? 0) : null,
        'unit' => $detail?->unit?->value ?? ($detail ? '' : '-'),
        'volume_target' => $index === 0 ? $row->volume_target : null,
      ];

      return array_map(fn($column) => $values[$column], $this->columns);
    })->all();
  }

  private function baseValues($row): array
  {
    return [
      'id' => $row->id,
      'year' => $row->year,
      'month' => date('F', mktime(0, 0, 0, $row->month, 10)),
      'province' => 'JAWA TIMUR',
      'regency' => $row->regency?->name ?? '-',
      'location' => $row->forest_type === 'Perhutanan Sosial'
      ? ($row->pengelolaWisata->name ?? '-')
      : ($row->forest_type === 'Hutan Negara'
        ? ($row->pengelolaHutan->name ?? '-')
        : ($row->district->name ?? '-')),
      'status' => $row->status,
      'creator' => $row->creator?->name ?? 'Unknown',
      'created_at' => $row->created_at?->format('d-m-Y H:i') ?? '-',
    ];
  }
}
