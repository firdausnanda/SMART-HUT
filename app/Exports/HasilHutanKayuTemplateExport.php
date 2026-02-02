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
  protected $forestType;

  public function __construct($forestType = 'Hutan Negara')
  {
    $this->kayus = Kayu::all();
    $this->forestType = $forestType;
  }

  public function headings(): array
  {
    $locationLabel = 'Nama Kecamatan';
    if ($this->forestType === 'Hutan Negara') {
      $locationLabel = 'Nama Pengelola Hutan';
    } elseif ($this->forestType === 'Perhutanan Sosial') {
      $locationLabel = 'Nama Pengelola Wisata';
    }

    $headers = [
      'Tahun',
      'Bulan (Angka)',
      'Nama Kabupaten',
      'Total Target (m3)',
      $locationLabel,
    ];

    foreach ($this->kayus as $kayu) {
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
        $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Total Target Volume (Angka) untuk periode dan lokasi ini');

        if ($this->forestType === 'Hutan Negara') {
          $sheet->getComment('E1')->getText()->createTextRun('Isi dengan Nama Pengelola Hutan');
        } elseif ($this->forestType === 'Perhutanan Sosial') {
          $sheet->getComment('E1')->getText()->createTextRun('Isi dengan Nama Pengelola Wisata');
        } else {
          $sheet->getComment('E1')->getText()->createTextRun('Isi dengan Nama Kecamatan');
        }

        $colIndex = 5; // Start at Column F (0-indexed: F=5)
        foreach ($this->kayus as $kayu) {
          // Realization Column
          $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
          $sheet->getComment($colLetter . '1')->getText()->createTextRun('Realisasi Volume (Angka) untuk ' . $kayu->name);
          $colIndex++;
        }
      },
    ];
  }
}
