<?php

namespace Modules\Kepegawaian\App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KgbExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder
{
    use BindsIdentifierColumnsAsText;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                'No' => $index + 1,
                'NIP' => $item['nip'],
                'Nama' => $item['nama'],
                'Pangkat/Golongan' => $item['pangkat_golongan'],
                'Unit Kerja' => $item['unit_kerja'],
                'TMT Terakhir' => $item['tmt_kgb_terakhir'] ?? '-',
                'TMT Berikutnya' => $item['tmt_kgb_berikutnya'],
                'Status' => $item['status'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Pegawai',
            'Pangkat/Golongan',
            'Unit Kerja',
            'TMT KGB Terakhir',
            'TMT KGB Berikutnya',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Proyeksi KGB';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '15803d']],
            ],
        ];
    }
}
