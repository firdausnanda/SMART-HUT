<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KupsTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
  public function headings(): array
  {
    return [
      'Nama Kabupaten/Kota',
      'Nama Kecamatan',
      'Kategori',
      'Jumlah KUPS',
      'Komoditas'
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
        $sheet->getComment('C1')->getText()->createTextRun('Contoh: Produksi, Pengolahan, dll');
        $sheet->getComment('D1')->getText()->createTextRun('Jumlah KUPS (angka)');
        $sheet->getComment('E1')->getText()->createTextRun('Contoh: Kopi, Coklat, Madu, dll');
      },
    ];
  }
}
