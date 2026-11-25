<?php

namespace Modules\Penanaman\App\Exports;

use App\Models\Penanaman;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PenanamanExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public const COLUMN_HEADINGS = [
        'year' => 'Tahun',
        'month' => 'Bulan (Angka)',
        'regency' => 'Nama Kabupaten',
        'district' => 'Nama Kecamatan',
        'village' => 'Nama Desa',
        'location_name' => 'Nama Lokasi',
        'coordinates' => 'Koordinat',
        'realization_ha' => 'Realisasi (Ha)',
        'fund_source' => 'Sumber Dana',
        'status' => 'Status',
        'creator' => 'Diinput Oleh',
        'created_at' => 'Tanggal Input',
    ];

    private array $columns;

    public function __construct(private User $user, private array $filters = [])
    {
        $this->columns = $filters['columns'] ?? array_keys(self::COLUMN_HEADINGS);
    }

    public function query()
    {
        return Penanaman::query()
            ->with(['regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name'])
            ->when(
                !$this->user->hasRole(['admin', 'admin_provinsi']),
                fn ($query) => $query->where('regency_id', $this->user->regency_id)
            )
            ->when($this->filters['year'] ?? null, fn ($query, $year) => $query->where('year', $year))
            ->when($this->filters['month'] ?? null, fn ($query, $month) => $query->where('month', $month))
            ->when(
                !empty($this->filters['status']) && $this->filters['status'] !== 'all',
                fn ($query) => $query->where('status', $this->filters['status'])
            )
            ->when($this->filters['regency_id'] ?? null, fn ($query, $regency) => $query->where('regency_id', $regency))
            ->when($this->filters['fund_source'] ?? null, fn ($query, $source) => $query->where('fund_source', $source))
            ->when($this->filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('location_name', 'like', "%{$search}%")
                        ->orWhere('fund_source', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('year')
            ->orderBy('month')
            ->orderBy('regency_id')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return array_map(fn ($key) => self::COLUMN_HEADINGS[$key], $this->columns);
    }

    public function map($row): array
    {
        $values = [
            'year' => $row->year,
            'month' => $row->month,
            'regency' => $row->regency?->name,
            'district' => $row->district?->name,
            'village' => $row->village?->name,
            'location_name' => $row->location_name,
            'coordinates' => $row->coordinates,
            'realization_ha' => $row->realization_ha !== null ? (float) $row->realization_ha : null,
            'fund_source' => $row->fund_source,
            'status' => ucfirst($row->status),
            'creator' => $row->creator?->name,
            'created_at' => $row->created_at?->format('d-m-Y H:i'),
        ];

        return array_map(fn ($key) => $values[$key], $this->columns);
    }

    public function title(): string
    {
        return 'Data Penanaman';
    }
}
