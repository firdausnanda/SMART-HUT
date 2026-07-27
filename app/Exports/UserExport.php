<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $roleFilter;
    protected $search;
    protected $cdkId;
    protected $isAdminProvinsi;

    public function __construct($request)
    {
        $this->roleFilter = $request->input('role_filter', 'with_role');
        $this->search = $request->input('search');
        $this->isAdminProvinsi = auth()->user()->isAdminProvinsi();
        $this->cdkId = auth()->user()->cdk_id;
    }

    public function query()
    {
        $query = User::with(['roles', 'cdk']);

        if (!$this->isAdminProvinsi) {
            $query->where('cdk_id', $this->cdkId);
        }

        if ($this->roleFilter === 'with_role') {
            $query->has('roles');
        } elseif ($this->roleFilter === 'without_role') {
            $query->doesntHave('roles');
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('name');
    }

    public function title(): string
    {
        return 'Data User';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Username',
            'Email',
            'Role',
            'CDK',
            'Tanggal Dibuat',
        ];
    }

    protected $rowNumber = 0;

    public function map($row): array
    {
        $this->rowNumber++;
        $roles = $row->roles->pluck('name')->join(', ');

        return [
            $this->rowNumber,
            $row->name,
            $row->username,
            $row->email,
            $roles ?: '-',
            $row->cdk ? $row->cdk->nama : '-',
            $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-',
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
