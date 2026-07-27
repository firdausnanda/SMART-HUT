<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            [
                'John Doe',
                'johndoe',
                'johndoe@example.com',
                'Password123!',
                'user',
                'Isi dengan Nama CDK (misal: CDK Trenggalek), kosongkan jika sesuai login pembuat',
            ]
        ];
    }

    public function title(): string
    {
        return 'Template Import User';
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'Username',
            'Email',
            'Password',
            'Role',
            'Nama CDK',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1D4ED8']],
            ],
        ];
    }
}
