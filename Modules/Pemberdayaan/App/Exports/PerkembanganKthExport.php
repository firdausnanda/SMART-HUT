<?php

namespace Modules\Pemberdayaan\App\Exports;

use App\Models\PerkembanganKth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PerkembanganKthExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public const COLUMN_HEADINGS = [
        'no' => 'No',
        'year' => 'Tahun',
        'month' => 'Bulan',
        'regency' => 'Kabupaten/Kota',
        'district' => 'Kecamatan',
        'village' => 'Desa',
        'nama_kth' => 'Nama KTH',
        'nomor_register' => 'Nomor Register',
        'kelas_kelembagaan' => 'Kelas Kelembagaan',
        'jumlah_anggota' => 'Jumlah Anggota',
        'luas_kelola' => 'Luas Kelola (Ha)',
        'potensi_kawasan' => 'Potensi Kawasan',
        'status' => 'Status',
        'creator' => 'Diinput Oleh',
    ];

    private $year;
    private array $filters;
    private array $columns;
    private int $rowNumber = 0;

    public function __construct($year = null, array $filters = [])
    {
        $this->year = $year;
        $this->filters = $filters;
        $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
    }

    public function collection()
    {
        return PerkembanganKth::query()
            ->with(['regency_rel', 'district_rel', 'village_rel', 'creator:id,name'])
            ->when($this->year, fn($q) => $q->where('year', $this->year))
            ->when($this->filters['month'] ?? null, fn($q, $month) => $q->where('month', $month))
            ->when(($this->filters['status'] ?? 'final') !== 'all', fn($q) => $q->where('status', $this->filters['status'] ?? 'final'))
            ->when($this->filters['cdk_id'] ?? null, fn($q, $id) => $q->where('cdk_id', $id))
            ->when($this->filters['regency_id'] ?? null, fn($q, $id) => $q->where('regency_id', $id))
            ->when($this->filters['district_id'] ?? null, fn($q, $id) => $q->where('district_id', $id))
            ->get();
    }

    public function headings(): array
    {
        return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $kelasLabels = ['pemula' => 'Pemula', 'madya' => 'Madya', 'utama' => 'Utama'];

        $values = [
            'no' => $this->rowNumber,
            'year' => $row->year,
            'month' => $months[$row->month] ?? $row->month,
            'regency' => $row->regency_rel?->name ?? '-',
            'district' => $row->district_rel?->name ?? '-',
            'village' => $row->village_rel?->name ?? '-',
            'nama_kth' => $row->nama_kth,
            'nomor_register' => $row->nomor_register ?? '-',
            'kelas_kelembagaan' => $kelasLabels[$row->kelas_kelembagaan] ?? $row->kelas_kelembagaan,
            'jumlah_anggota' => $row->jumlah_anggota,
            'luas_kelola' => $row->luas_kelola,
            'potensi_kawasan' => $row->potensi_kawasan ?? '-',
            'status' => ucfirst($row->status),
            'creator' => $row->creator?->name ?? '-',
        ];

        return array_map(fn($column) => $values[$column], $this->columns);
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
