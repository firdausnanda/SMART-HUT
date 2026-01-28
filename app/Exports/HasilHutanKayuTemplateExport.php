<?php

namespace App\Exports;

use App\Models\Kayu;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HasilHutanKayuTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
  protected $kayus;

  public function __construct()
  {
    $this->kayus = Kayu::all();
  }

  public function headings(): array
  {
    $headers = [
      'Tahun',
      'Bulan (Angka)',
      'Nama Kabupaten',
      'Nama Kecamatan',
    ];

    foreach ($this->kayus as $kayu) {
      $headers[] = $kayu->name . ' - Target';
      $headers[] = $kayu->name . ' - Realisasi';
    }

    return $headers;
  }

  public function title(): string
  {
    return 'Template Import';
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']]],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Month Validation (1-12)
        $validation = $sheet->getCell('B2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_WHOLE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setFormula1(1);
        $validation->setFormula2(12);
        $validation->setErrorTitle('Input Error');
        $validation->setError('Bulan harus angka 1-12');
        $validation->setPromptTitle('Bulan');
        $validation->setPrompt('Masukkan angka 1-12');

        // Apply to column B
        for ($i = 2; $i <= 1000; $i++) {
          $sheet->getCell("B$i")->setDataValidation(clone $validation);
        }

        // Add comments
        $sheet->getComment('C1')->getText()->createTextRun('Isi dengan Nama Kabupaten/Kota (KABUPATEN TRENGGALEK,KABUPATEN TULUNGAGUNG,KABUPATEN KEDIRI,KOTA KEDIRI)');
        $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Nama Kecamatan (e.g. KECAMATAN TRENGGALEK)');

        $colIndex = 4; // Start at Column E (0-indexed: A=0, B=1, C=2, D=3, E=4)
        foreach ($this->kayus as $kayu) {
          // Target Column
          $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
          $sheet->getComment($colLetter . '1')->getText()->createTextRun('Target Volume (Angka) untuk ' . $kayu->name);
          $colIndex++;

          // Realization Column
          $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
          $sheet->getComment($colLetter . '1')->getText()->createTextRun('Realisasi Volume (Angka) untuk ' . $kayu->name);
          $colIndex++;
        }
      },
    ];
  }
}
