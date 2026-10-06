<?php

namespace Modules\Pemberdayaan\App\Exports;

use App\Models\Skps;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SkpsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public const COLUMN_HEADINGS = [
        'no' => 'No',
        'regency' => 'Kabupaten/Kota',
        'district' => 'Kecamatan',
        'group_name' => 'Nama Kelompok',
        'scheme' => 'Skema Perhutanan Sosial',
        'potential' => 'Potensi (Ha)',
        'ps_area' => 'Luas PS (Ha)',
        'number_of_kk' => 'Jumlah KK',
        'status' => 'Status',
        'creator' => 'Diinput Oleh',
        'created_at' => 'Tanggal Input',
    ];

    private array $filters;
    private array $columns;
    private int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
    }

    public function query()
    {
        return Skps::query()
            ->with(['creator:id,name', 'regency', 'district', 'skema'])
            ->when(($this->filters['status'] ?? 'final') !== 'all', fn($q) => $q->where('status', $this->filters['status'] ?? 'final'))
            ->when($this->filters['cdk_id'] ?? null, fn($q, $id) => $q->where('cdk_id', $id))
            ->when($this->filters['regency_id'] ?? null, fn($q, $id) => $q->where('regency_id', $id))
            ->when($this->filters['district_id'] ?? null, fn($q, $id) => $q->where('district_id', $id))
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return array_map(fn($column) => self::COLUMN_HEADINGS[$column], $this->columns);
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $values = [
            'no' => $this->rowNumber,
            'regency' => $row->regency?->name ?? '-',
            'district' => $row->district?->name ?? '-',
            'group_name' => $row->nama_kelompok ?? '-',
            'scheme' => $row->skema?->name ?? '-',
            'potential' => $row->potential,
            'ps_area' => $row->ps_area,
            'number_of_kk' => $row->number_of_kk,
            'status' => ucfirst($row->status),
            'creator' => $row->creator?->name ?? 'Unknown',
            'created_at' => $row->created_at?->format('d-m-Y H:i') ?? '-',
        ];

        return array_map(fn($column) => $values[$column], $this->columns);
    }

    public function title(): string
    {
        return 'Perkembangan SK PS';
    }
}
