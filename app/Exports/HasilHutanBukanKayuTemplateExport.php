<?php

namespace App\Exports;

use App\Models\BukanKayu;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class HasilHutanBukanKayuTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
  protected $commodities;

  public function __construct()
  {
    $this->commodities = \App\Models\Commodity::all();
  }

  public function headings(): array
  {
    $headers = [
      'Tahun',
      'Bulan (Angka)',
      'Nama Kabupaten',
      'Nama Kecamatan',
    ];

    foreach ($this->commodities as $commodity) {
      $headers[] = $commodity->name . ' - Target';
      $headers[] = $commodity->name . ' - Realisasi';
    }

    return $headers;
  }

  public function title(): string
  {
    return 'Template Import Data';
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
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

        for ($i = 2; $i <= 1000; $i++) {
          $sheet->getCell("B$i")->setDataValidation(clone $validation);
        }

        // Freeze the first row (headers) and first 4 columns (Location info)
        $sheet->freezePane('E2');

        $sheet->getComment('C1')->getText()->createTextRun('Isi dengan Nama Kabupaten (e.g. KABUPATEN TRENGGALEK)');
        $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Nama Kecamatan (e.g. KECAMATAN PANGGUL)');
      },
    ];
  }
}
