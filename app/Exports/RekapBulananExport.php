<?php

namespace App\Exports;

use App\Models\RekapBulananPegawai;
use App\Models\RekapStatistikBulanan;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapBulananExport implements WithMultipleSheets
{
    protected $year;
    protected $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function sheets(): array
    {
        return [
            new RekapStatistikSheet($this->year, $this->month),
            new RekapDetailPegawaiSheet($this->year, $this->month),
            new RekapBezettingSheet($this->year, $this->month),
        ];
    }
}

class RekapStatistikSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function title(): string
    {
        return 'Ringkasan Statistik';
    }

    public function collection()
    {
        $rekap = RekapStatistikBulanan::where('periode_tahun', $this->year)
            ->where('periode_bulan', $this->month)
            ->first();

        if (!$rekap) return collect([]);

        return collect([
            ['Kategori', 'Total'],
            ['Total Pegawai Aktif', $rekap->total_pegawai_aktif],
            ['Total PNS', $rekap->total_pns],
            ['Total PPPK', $rekap->total_pppk],
            ['Total Honorer/Lainnya', $rekap->total_honorer],
            ['Laki-laki', $rekap->total_laki],
            ['Perempuan', $rekap->total_perempuan],
            ['Pensiun Tahun Ini', $rekap->total_pensiun_tahun_ini],
            ['Pensiun Bulan Ini', $rekap->total_pensiun_bulan_ini],
            ['Pensiun Dalam 6 Bulan', $rekap->total_pensiun_6_bulan],
            ['KGB Jatuh Bulan Ini', $rekap->kgb_jatuh_bulan_ini],
            ['KGB Jatuh Dalam 3 Bulan', $rekap->kgb_jatuh_3_bulan],
        ]);
    }

    public function headings(): array
    {
        return ["Rekap Kepegawaian Periode {$this->month}-{$this->year}"];
    }

    public function styles(Worksheet $worksheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}

class RekapDetailPegawaiSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;
    protected $rowNumber = 0;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function title(): string
    {
        return 'Detail Snapshot Pegawai';
    }

    public function collection()
    {
        return RekapBulananPegawai::forPeriode($this->year, $this->month)->get();
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama Lengkap', 'Status Pegawai', 'Golongan', 'Jabatan', 'Unit Kerja', 'Pendidikan', 'Generasi', 'Usia', 'Masa Kerja (Thn)', 'Gaji Terakhir', 'Proyeksi Pensiun', 'KGB Berikutnya'
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $row->nip . ' ', // force string
            $row->nama_lengkap,
            $row->status_pegawai,
            $row->pangkat_golongan,
            $row->nama_jabatan_snapshot,
            $row->unit_kerja,
            $row->pendidikan_terakhir,
            $row->generasi,
            $row->usia_per_periode,
            $row->masa_kerja_tahun,
            $row->gaji_pokok_terakhir,
            $row->proyeksi_pensiun ? $row->proyeksi_pensiun->format('d-m-Y') : '-',
            $row->tmt_kgb_berikutnya ? $row->tmt_kgb_berikutnya->format('d-m-Y') : '-',
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

class RekapBezettingSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;

    public function __construct($year, $month)
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
            $data[] = [
                $jabatan,
                $stats['kebutuhan'],
                $stats['terisi'],
                $stats['selisih'],
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['Nama Jabatan', 'Kebutuhan (ABK)', 'Terisi', 'Selisih (+/-)'];
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
