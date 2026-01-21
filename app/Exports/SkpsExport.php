<?php

namespace App\Exports;

use App\Models\Skps;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SkpsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
  public function query()
  {
    return Skps::query()
      ->with(['creator', 'regency', 'district', 'skema'])
      ->where('status', 'final')
      ->orderBy('created_at', 'desc');
  }

  public function headings(): array
  {
    return [
      'No',
      'Kabupaten/Kota',
      'Kecamatan',
      'Skema Perhutanan Sosial',
      'Potensi (Ha)',
      'Luas PS (Ha)',
      'Jumlah KK',
      'Status',
      'Diinput Oleh',
      'Tanggal Input'
    ];
  }

  public function map($row): array
  {
    static $no = 0;
    $no++;

    return [
      $no,
      $row->regency->name ?? '-',
      $row->district->name ?? '-',
      $row->skema->name ?? '-',
      $row->potential,
      $row->ps_area,
      $row->number_of_kk,
      ucfirst($row->status),
      $row->creator->name ?? 'Unknown',
      $row->created_at->format('d-m-Y H:i'),
    ];
  }

  public function title(): string
  {
    return 'Perkembangan SK PS';
  }
}
