<?php

namespace App\Exports;

use App\Models\RhlTeknis;
use App\Models\RhlTeknisDetail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RhlTeknisExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
  protected $year;

  public function __construct($year = null)
  {
    $this->year = $year;
  }

  public function query()
  {
    return RhlTeknisDetail::query()
      ->select('rhl_teknis_details.*')
      ->join('rhl_teknis', 'rhl_teknis.id', '=', 'rhl_teknis_details.rhl_teknis_id')
      ->with(['rhl_teknis.creator', 'rhl_teknis.regency', 'rhl_teknis.district', 'rhl_teknis.village', 'bangunan_kta'])
      ->where('rhl_teknis.status', 'final')
      ->when($this->year, fn($q) => $q->where('rhl_teknis.year', $this->year))
      ->orderBy('rhl_teknis.year', 'desc')
      ->orderBy('rhl_teknis.month', 'asc')
      ->orderBy('rhl_teknis.id', 'desc');
  }

  public function headings(): array
  {
    return [
      'No', 
      'Tahun', 
      'Bulan', 
      'Kabupaten', 
      'Kecamatan', 
      'Desa', 
      'Target Tahunan (Ha)', 
      'Sumber Dana', 
      'Jenis Bangunan',
      'Jumlah Unit',
      'Status', 
      'Diinput Oleh', 
      'Tanggal Input'
    ];
  }

  public function map($detail): array
  {
    static $no = 0;
    $no++;
    
    $row = $detail->rhl_teknis;
    
    $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $fundSourceLabels = ['apbn' => 'APBN', 'apbd' => 'APBD', 'swasta' => 'Swasta', 'swadaya' => 'Swadaya Masyarakat', 'other' => 'Lainnya'];

    return [
      $no,
      $row->year,
      $monthNames[$row->month] ?? $row->month,
      $row->regency->name ?? '-',
      $row->district->name ?? '-',
      $row->village->name ?? '-',
      number_format($row->target_annual, 2, ',', '.'),
      $fundSourceLabels[$row->fund_source] ?? $row->fund_source,
      $detail->bangunan_kta->name ?? '-',
      $detail->unit_amount,
      ucfirst($row->status),
      $row->creator->name ?? 'Unknown',
      $row->created_at->format('d-m-Y H:i'),
    ];
  }

  public function title(): string
  {
    return 'RHL Teknis ' . ($this->year ?? 'Semua Tahun');
  }
}
