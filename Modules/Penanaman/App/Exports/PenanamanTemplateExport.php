<?php

namespace Modules\Penanaman\App\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenanamanTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
    public function headings(): array
    {
        return array_values(array_slice(PenanamanExport::COLUMN_HEADINGS, 0, 9));
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
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F6E46']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $validation = $sheet->getCell('B2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_WHOLE);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowErrorMessage(true);
                $validation->setFormula1(1);
                $validation->setFormula2(12);
                $validation->setError('Bulan harus berupa angka 1–12.');

                for ($row = 2; $row <= 1000; $row++) {
                    $sheet->getCell("B{$row}")->setDataValidation(clone $validation);
                }

                $sheet->getComment('C1')->getText()->createTextRun('Gunakan nama kabupaten/kota sesuai master Jawa Timur.');
                $sheet->getComment('D1')->getText()->createTextRun('Gunakan kecamatan di kabupaten/kota terpilih.');
                $sheet->getComment('E1')->getText()->createTextRun('Gunakan desa/kelurahan di kecamatan terpilih.');
                $sheet->getComment('I1')->getText()->createTextRun('Gunakan nama sesuai Master Sumber Dana.');
            },
        ];
    }
}
