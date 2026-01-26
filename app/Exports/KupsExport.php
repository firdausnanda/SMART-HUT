<?php

namespace App\Exports;

use App\Models\Kups;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class KupsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
  public function query()
  {
    return Kups::query()
      ->with(['province', 'regency', 'district'])
      ->where('status', 'finalized')
      ->orderBy('created_at', 'desc');
  }

  public function headings(): array
  {
    return [
      'No',
      'Kabupaten/Kota',
      'Kecamatan',
      'Kategori',
      'Komoditas',
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
      $row->category,
      $row->commodity,
      ucfirst($row->status),
      $row->creator->name ?? 'Unknown',
      $row->created_at->format('d-m-Y H:i'),
    ];
  }

  public function title(): string
  {
    return 'Perkembangan KUPS';
  }
}
