<?php

namespace App\Exports;

use App\Models\HasilHutanBukanKayu;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HasilHutanBukanKayuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
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
    return HasilHutanBukanKayu::query()
      ->with(['regency', 'district', 'pengelolaHutan', 'pengelolaWisata', 'details.commodity', 'creator'])
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
      'Bulan (Angka)',
      'Provinsi',
      'Kabupaten/Kota',
      'Kecamatan / Pengelola',
      'Komoditas (Realisasi)', // Combined column
      'Total Target',
      'Total Realisasi',
      'Status',
      'Dibuat Oleh',
      'Tanggal Input',
    ];
  }

  public function map($row): array
  {
    $detailsString = $row->details->map(function ($d) {
      $real = $d->annual_volume_realization ?? 0;
      return ($d->commodity->name ?? '?') . ': ' . $real . ' ' . $d->unit;
    })->join(",\n");

    $totalVolume = $row->volume_target;
    $totalRealization = $row->details->sum('annual_volume_realization');

    return [
      $row->id,
      $row->year,
      date('F', mktime(0, 0, 0, $row->month, 10)),
      'JAWA TIMUR',
      $row->regency->name ?? '-',
      $row->forest_type === 'Perhutanan Sosial'
      ? ($row->pengelolaWisata->name ?? '-')
      : ($row->forest_type === 'Hutan Negara'
        ? ($row->pengelolaHutan->name ?? '-')
        : ($row->district->name ?? '-')),
      $detailsString ?: '-',
      $totalVolume,
      $totalRealization,
      $row->status,
      $row->creator->name ?? 'Unknown',
      $row->created_at->format('d-m-Y H:i'),
    ];
  }
}
