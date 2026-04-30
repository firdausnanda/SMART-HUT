<?php

namespace App\Exports;

use App\Models\RekapStatistikBulanan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BezettingExport implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function title(): string
    {
        return 'Analisa Bezetting';
    }

    public function collection()
    {
        $rekap = RekapStatistikBulanan::where('periode_tahun', $this->year)
            ->where('periode_bulan', $this->month)
            ->first();

        if (!$rekap || !$rekap->statistik_bezetting) return collect([]);

        $data = [];
        foreach ($rekap->statistik_bezetting as $jabatan => $stats) {
            $status = 'Ideal';
            if ($stats['selisih'] > 0) {
                $status = "Kurang {$stats['selisih']}";
            } else if ($stats['selisih'] < 0) {
                $status = "Kelebihan " . abs($stats['selisih']);
            }

            $data[] = [
                $jabatan,
                $stats['kebutuhan'],
                $stats['terisi'],
                $stats['selisih'],
                $status
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['Nama Jabatan', 'Kebutuhan (ABK)', 'Terisi Saat Ini', 'Selisih', 'Status'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
