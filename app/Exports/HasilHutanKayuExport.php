<?php

namespace App\Exports;

use App\Models\HasilHutanKayu;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HasilHutanKayuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
  protected $forestType;
  protected $year;

  public function __construct($forestType, $year = null)
  {
    $this->forestType = $forestType;
    $this->year = $year;
  }

  public function query()
  {
    return HasilHutanKayu::query()
      ->with(['regency', 'district', 'details.kayu', 'creator'])
      ->where('forest_type', $this->forestType)
      ->when($this->year, function ($q) {
        return $q->where('year', $this->year);
      });
  }

  public function headings(): array
  {
    return [
      'ID',
      'Tahun',
      'Bulan',
      'Provinsi',
      'Kabupaten',
      'Kecamatan',
      'Detail Kayu (Jenis)',
      'Total Target (m3)',
      'Total Realisasi (m3)',
      'Status',
      'Diinput Oleh',
      'Tanggal Input',
    ];
  }

  public function map($row): array
  {
    $detailsString = $row->details->map(function ($detail) {
      return $detail->kayu->name ?? '-';
    })->implode(', ');

    return [
      $row->id,
      $row->year,
      date('F', mktime(0, 0, 0, $row->month, 10)), // Month name
      'JAWA TIMUR',
      $row->regency->name ?? '-',
      $row->district->name ?? '-',
      $detailsString,
      $row->annual_volume_target,
      $row->annual_volume_realization,
      $row->status,
      $row->creator->name ?? 'Unknown',
      $row->created_at->format('d-m-Y H:i'),
    ];
  }
}
