<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RealisasiPnbpTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
  public function headings(): array
  {
    return [
      'Tahun',
      'Bulan (Angka 1-12)',
      'Nama Kabupaten/Kota',
      'Nama Pengelola Wisata',
      'Jenis Hasil Hutan',
      'Target PNBP',
      'Realisasi PNBP'
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
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']]
      ]
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Month validation (1-12)
        $monthValidation = $sheet->getCell('B2')->getDataValidation();
        $monthValidation->setType(DataValidation::TYPE_WHOLE)
          ->setErrorStyle(DataValidation::STYLE_STOP)
          ->setAllowBlank(false)
          ->setShowInputMessage(true)
          ->setShowErrorMessage(true)
          ->setFormula1(1)
          ->setFormula2(12)
          ->setErrorTitle('Input Error')
          ->setError('Bulan harus angka 1-12');

        for ($i = 2; $i <= 1000; $i++) {
          $sheet->getCell("B$i")->setDataValidation(clone $monthValidation);
        }

        // Add helpful comments
        $sheet->getComment('C1')->getText()->createTextRun('Contoh: TRENGGALEK, TULUNGAGUNG');
        $sheet->getComment('D1')->getText()->createTextRun('Nama Pengelola Wisata dari master data');
        $sheet->getComment('E1')->getText()->createTextRun('Contoh: Kayu Jati, Kayu Mahoni, Rotan');
        $sheet->getComment('F1')->getText()->createTextRun('Target PNBP dalam Rupiah');
        $sheet->getComment('G1')->getText()->createTextRun('Realisasi PNBP dalam Rupiah');
      },
    ];
  }
}
