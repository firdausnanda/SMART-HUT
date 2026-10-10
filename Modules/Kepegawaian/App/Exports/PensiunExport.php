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

class PensiunExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder
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
                'Tgl Lahir' => $item['tanggal_lahir'],
                'BUP' => $item['bup'] . ' Tahun',
                'TMT Pensiun' => $item['tmt_pensiun'],
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
            'Tanggal Lahir',
            'Batas Usia Pensiun',
            'TMT Pensiun',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Proyeksi Pensiun';
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
