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
  protected $forestType;

  public function __construct($forestType = 'Hutan Negara')
  {
    $orderedNames = [
      'Bambu',
      'Getah Pinus',
      'Daun Kayu Putih',
      'Porang',
      'Kopi',
      'Madu',
      'Durian',
      'Alpukat',
      'Jahe',
      'Kunyit'
    ];
    $all = BukanKayu::all();
    $this->commodities = $all->sortBy(function ($model) use ($orderedNames) {
      $index = array_search($model->name, $orderedNames);
      return $index === false ? 9999 + $model->id : $index;
    })->values();
    $this->forestType = $forestType;
  }

  public function headings(): array
  {
    if ($this->forestType === 'Hutan Negara') {
      $headers = [
        'Tahun',
        'Bulan (Angka)',
        'Nama Kabupaten',
        'Nama Pengelola',
        'Total Target',
      ];
    } elseif ($this->forestType === 'Perhutanan Sosial') {
      $headers = [
        'Tahun',
        'Bulan (Angka)',
        'Nama Kabupaten',
        'Nama Pengelola Wisata',
        'Total Target',
      ];
    } else {
      $headers = [
        'Tahun',
        'Bulan (Angka)',
        'Nama Kabupaten',
        'Nama Kecamatan',
        'Total Target',
      ];
    }

    foreach ($this->commodities as $commodity) {
      $headers[] = $commodity->name . ' - Realisasi';
      $headers[] = $commodity->name . ' - Satuan';
    }

    return $headers;
  }

  public function title(): string
  {
    return 'Template Import Data ' . $this->forestType;
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

        // Freeze Pane based on location info
        $sheet->freezePane('F2');

        $sheet->getComment('C1')->getText()->createTextRun('Isi dengan Nama Kabupaten (e.g. KABUPATEN TRENGGALEK)');

        if ($this->forestType === 'Hutan Negara') {
          $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Nama Pengelola (e.g. RPH PANGGUL)');
        } elseif ($this->forestType === 'Perhutanan Sosial') {
          $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Nama Pengelola Wisata');
        } else {
          $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Nama Kecamatan (e.g. KECAMATAN PANGGUL)');
        }
      },
    ];
  }
}
