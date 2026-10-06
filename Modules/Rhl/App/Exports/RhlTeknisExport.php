<?php

namespace Modules\Rhl\App\Exports;

use App\Models\RhlTeknisDetail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RhlTeknisExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
  public const COLUMN_HEADINGS = [
    'no' => 'No', 'year' => 'Tahun', 'month' => 'Bulan', 'regency' => 'Kabupaten',
    'district' => 'Kecamatan', 'village' => 'Desa', 'target_annual' => 'Target Tahunan (Ha)',
    'fund_source' => 'Sumber Dana', 'building' => 'Jenis Bangunan', 'unit_amount' => 'Jumlah Unit',
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
    return RhlTeknisDetail::query()
      ->select('rhl_teknis_details.*')
      ->join('rhl_teknis', 'rhl_teknis.id', '=', 'rhl_teknis_details.rhl_teknis_id')
      ->with(['rhl_teknis.creator:id,name', 'rhl_teknis.regency', 'rhl_teknis.district', 'rhl_teknis.village', 'bangunan_kta'])
      ->whereHas('rhl_teknis', function ($q) {
        $q->when(($this->filters['status'] ?? 'final') !== 'all', fn($q) => $q->where('status', $this->filters['status'] ?? 'final'))
          ->when($this->year, fn($q) => $q->where('year', $this->year))
          ->when($this->filters['month'] ?? null, fn($q, $value) => $q->where('month', $value))
          ->when($this->filters['cdk_id'] ?? null, fn($q, $value) => $q->where('cdk_id', $value))
          ->when($this->filters['regency_id'] ?? null, fn($q, $value) => $q->where('regency_id', $value))
          ->when($this->filters['district_id'] ?? null, fn($q, $value) => $q->where('district_id', $value))
          ->when($this->filters['fund_source'] ?? null, fn($q, $value) => $q->where('fund_source', $value));
      })
      ->when($this->filters['bangunan_kta_id'] ?? null, fn($q, $value) => $q->where('rhl_teknis_details.bangunan_kta_id', $value))
      ->orderBy('rhl_teknis.year', 'desc')
      ->orderBy('rhl_teknis.month', 'asc')
      ->orderBy('rhl_teknis.id', 'desc');
  }

  public function headings(): array
  {
    return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
  }

  public function map($detail): array
  {
    $this->rowNumber++;
    
    $row = $detail->rhl_teknis;
    
    $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $fundSourceLabels = ['apbn' => 'APBN', 'apbd' => 'APBD', 'swasta' => 'Swasta', 'swadaya' => 'Swadaya Masyarakat', 'other' => 'Lainnya'];

    $values = [
      'no' => $this->rowNumber,
      'year' => $row->year,
      'month' => $monthNames[$row->month] ?? $row->month,
      'regency' => $row->regency?->name ?? '-',
      'district' => $row->district?->name ?? '-',
      'village' => $row->village?->name ?? '-',
      'target_annual' => number_format($row->target_annual, 2, ',', '.'),
      'fund_source' => $fundSourceLabels[$row->fund_source] ?? $row->fund_source,
      'building' => $detail->bangunan_kta?->name ?? '-',
      'unit_amount' => $detail->unit_amount,
      'status' => ucfirst($row->status),
      'creator' => $row->creator?->name ?? 'Unknown',
      'created_at' => $row->created_at?->format('d-m-Y H:i') ?? '-',
    ];

    return array_map(fn($column) => $values[$column], $this->columns);
  }

  public function title(): string
  {
    return 'RHL Teknis ' . ($this->year ?? 'Semua Tahun');
  }
}
