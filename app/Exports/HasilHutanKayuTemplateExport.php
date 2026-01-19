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
  public function headings(): array
  {
    return [
      'Tahun',
      'Bulan (Angka)',
      'Nama Kabupaten',
      'Nama Kecamatan',
      'Jenis Kayu',
      'Target Volume',
    ];
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
        // Get Data for Dropdowns
        $kayus = Kayu::pluck('name')->toArray();
        $regencies = DB::table('m_regencies')->where('province_id', 35)->pluck('name')->toArray();

        // Dropdowns need to be comma separated string (max 255 chars usually, or ref to other sheet)
        // Since lists might be long, putting them directly in validation might fail if > 255 chars.
        // Best practice is to create a Reference sheet, but for simplicity here I'll try partial or just let user type if list is long.
        // But Kayu might be short.
        // Regencies (38 items) * ~10 chars = ~400 chars > 255. So we need a hidden sheet or just allow typing.
  
        // Let's add comments to help user instead of complex validation for now to avoid Excel breakage,
        // OR add validation for Month (1-12).
  
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

        // Add comments for other columns
        $sheet->getComment('C1')->getText()->createTextRun('Isi dengan Nama Kabupaten/Kota (KABUPATEN TRENGGALEK,KABUPATEN TULUNGAGUNG,KABUPATEN KEDIRI,KOTA KEDIRI)');
        $sheet->getComment('D1')->getText()->createTextRun('Isi dengan Nama Kecamatan (e.g. KECAMATAN TRENGGALEK)');
        $sheet->getComment('E1')->getText()->createTextRun('Isi dengan Nama Jenis Kayu (e.g. JATI)');
      },
    ];
  }
}
