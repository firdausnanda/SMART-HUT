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

class KupsTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
  public function headings(): array
  {
    return [
      'Nama Kabupaten/Kota',
      'Nama Kecamatan',
      'Nama KUPS',
      'Kategori',
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
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']] // Emerald-600 to match RehabLahan/KUPS theme
      ]
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Validation for Kategori
        $validation = $sheet->getCell('D2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"Blue,Silver,Gold,Platinum"');
        $validation->setErrorTitle('Input Error');
        $validation->setError('Pilih kategori yang tersedia.');
        $validation->setPromptTitle('Kategori');
        $validation->setPrompt('Pilih kategori: Blue, Silver, Gold, Platinum');

        // Apply to column D
        for ($i = 2; $i <= 1000; $i++) {
          $sheet->getCell("D$i")->setDataValidation(clone $validation);
        }

        // Add helpful comments
        $sheet->getComment('A1')->getText()->createTextRun('Contoh: TRENGGALEK, TULUNGAGUNG');
        $sheet->getComment('B1')->getText()->createTextRun('Contoh: WATULIMO, MUNJUNGAN');
        $sheet->getComment('C1')->getText()->createTextRun('Contoh: KUPS Tani Makmur');
        $sheet->getComment('D1')->getText()->createTextRun('Pilih salah satu: Blue, Silver, Gold, Platinum');
        $sheet->getComment('E1')->getText()->createTextRun('Contoh: Kopi, Coklat, Madu, dll');
      },
    ];
  }
}
