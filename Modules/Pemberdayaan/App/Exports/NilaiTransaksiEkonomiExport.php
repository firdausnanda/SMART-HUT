<?php

namespace Modules\Pemberdayaan\App\Exports;

use App\Models\NilaiTransaksiEkonomi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiTransaksiEkonomiExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
  public const COLUMN_HEADINGS = [
    'no' => 'No',
    'year' => 'Tahun',
    'month' => 'Bulan',
    'regency' => 'Kabupaten/Kota',
    'district' => 'Kecamatan',
    'village' => 'Desa',
    'nama_kth' => 'Nama KTH',
    'commodity' => 'Komoditas',
    'volume_produksi' => 'Volume Produksi',
    'satuan' => 'Satuan',
    'nilai_transaksi' => 'Nilai Transaksi (Rp)',
    'status' => 'Status',
    'creator' => 'Input Oleh',
  ];

  protected $year;
  protected array $filters;
  protected array $columns;
  protected int $rowNumber = 0;

  public function __construct($year = null, array $filters = [])
  {
    $this->year = $year;
    $this->filters = $filters;
    $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
  }

  public function collection()
  {
    // We want to export details, so we load them
    $records = NilaiTransaksiEkonomi::query()
      ->with([
        'regency_rel',
        'district_rel',
        'village_rel',
        'creator:id,name',
        'details.commodity' => fn($q) => $q->withoutGlobalScope('not_nilai_transaksi_ekonomi')
      ])
      ->when($this->year, fn($q) => $q->where('year', $this->year))
      ->when($this->filters['cdk_id'] ?? null, fn($q, $id) => $q->where('cdk_id', $id))
      ->when(($this->filters['status'] ?? 'final') !== 'all', fn($q) => $q->where('status', $this->filters['status'] ?? 'final'))
      ->when($this->filters['month'] ?? null, fn($q, $month) => $q->where('month', $month))
      ->when($this->filters['regency_id'] ?? null, fn($q, $id) => $q->where('regency_id', $id))
      ->when($this->filters['district_id'] ?? null, fn($q, $id) => $q->where('district_id', $id))
      ->when($this->filters['commodity_id'] ?? null, fn($q, $id) => $q->whereHas('details', fn($details) => $details->where('commodity_id', $id)))
      ->get();

    $rows = collect();
    foreach ($records as $record) {
      foreach ($record->details as $detail) {
        if (($this->filters['commodity_id'] ?? null) && $detail->commodity_id != $this->filters['commodity_id']) {
          continue;
        }
        // Attach the parent record info to each detail for mapping
        $detail->parent = $record;
        $rows->push($detail);
      }
    }

    return $rows;
  }

  public function headings(): array
  {
    return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
  }

  public function map($detail): array
  {
    $this->rowNumber++;

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

    $values = [
      'no' => $this->rowNumber,
      'year' => $row->year,
      'month' => $months[$row->month] ?? $row->month,
      'regency' => $row->regency_rel?->name ?? '-',
      'district' => $row->district_rel?->name ?? '-',
      'village' => $row->village_rel?->name ?? '-',
      'nama_kth' => $row->nama_kth,
      'commodity' => $detail->commodity?->name ?? '-',
      'volume_produksi' => $detail->volume_produksi,
      'satuan' => $detail->satuan,
      'nilai_transaksi' => $detail->nilai_transaksi,
      'status' => ucfirst($row->status),
      'creator' => $row->creator?->name ?? '-',
    ];

    return array_map(fn($column) => $values[$column], $this->columns);
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
