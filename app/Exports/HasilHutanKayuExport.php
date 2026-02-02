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
      ->with(['regency', 'district', 'pengelolaHutan', 'pengelolaWisata', 'details.kayu', 'creator'])
      ->where('forest_type', $this->forestType)
      ->when($this->year, function ($q) {
        return $q->where('year', $this->year);
      });
  }

  public function headings(): array
  {
    $locationLabel = 'Kecamatan';
    if ($this->forestType === 'Hutan Negara') {
      $locationLabel = 'Pengelola Hutan';
    } elseif ($this->forestType === 'Perhutanan Sosial') {
      $locationLabel = 'Pengelola Wisata';
    }

    return [
      'ID',
      'Tahun',
      'Bulan',
      'Provinsi',
      'Kabupaten',
      $locationLabel,
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

    return [
      $row->id,
      $row->year,
      date('F', mktime(0, 0, 0, $row->month, 10)), // Month name
      'JAWA TIMUR',
      $row->regency->name ?? '-',
      $locationName,
      $detailsString,
      $row->volume_target,
      $row->details->sum('volume_realization'),
      $row->status,
      $row->creator->name ?? 'Unknown',
      $row->created_at->format('d-m-Y H:i'),
    ];
  }
}
