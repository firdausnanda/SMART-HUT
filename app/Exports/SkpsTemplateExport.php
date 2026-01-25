<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SkpsTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
  public function headings(): array
  {
    return [
      'Nama Kabupaten/Kota',
      'Nama Kecamatan',
      'Nama Kelompok',
      'Nama Skema Perhutanan Sosial',
      'Potensi (Ha)',
      'Luas PS (Ha)',
      'Jumlah KK'
    ];
  }

  public function title(): string
  {
    return 'Template Import';
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '7C3AED']]
      ]
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Add helpful comments
        $sheet->getComment('A1')->getText()->createTextRun('Contoh: TRENGGALEK, TULUNGAGUNG');
        $sheet->getComment('B1')->getText()->createTextRun('Contoh: WATULIMO, MUNJUNGAN');
        $sheet->getComment('C1')->getText()->createTextRun('Contoh nama skema dari master data seperti: Hutan Desa, Hutan Kemasyarakatan, dll');
        $sheet->getComment('D1')->getText()->createTextRun('Luas potensi dalam hektar (angka)');
        $sheet->getComment('E1')->getText()->createTextRun('Luas PS dalam hektar (angka)');
        $sheet->getComment('F1')->getText()->createTextRun('Jumlah Kepala Keluarga (angka)');
      },
    ];
  }
}
