<?php

namespace Modules\BinaUsaha\App\Exports;

use App\Models\HasilHutanBukanKayu;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HasilHutanBukanKayuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
  protected $forestType;
  protected $year;
  protected array $filters;

  public function __construct($forestType, $year = null, array $filters = [])
  {
    $this->forestType = $forestType;
    $this->year = $year;
    $this->filters = $filters;
  }

  public function query()
  {
    return HasilHutanBukanKayu::query()
      ->with(['regency', 'district', 'pengelolaHutan', 'pengelolaWisata', 'details.bukanKayu', 'creator'])
      ->where('forest_type', $this->forestType)
      ->when($this->year, function ($q) {
        return $q->where('year', $this->year);
      })
      ->when($this->filters['month'] ?? null, fn($q, $month) => $q->where('month', $month))
      ->when(($this->filters['status'] ?? 'all') !== 'all', fn($q) => $q->where('status', $this->filters['status']))
      ->when($this->filters['regency_id'] ?? null, fn($q, $id) => $q->where('regency_id', $id))
      ->when($this->filters['district_id'] ?? null, fn($q, $id) => $q->where('district_id', $id));
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
      return ($d->bukanKayu->name ?? '?') . ': ' . $real . ' ' . $d->unit;
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
