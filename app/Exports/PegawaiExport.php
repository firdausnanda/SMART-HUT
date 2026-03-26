<?php

namespace App\Exports;

use App\Models\Pegawai;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PegawaiExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function query()
    {
        return Pegawai::with('bezetting', 'creator')->orderBy('nama_lengkap');
    }

    public function title(): string
    {
        return 'Data Demografi Pegawai';
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Lengkap',
            'NIK',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',
            'Pendidikan Terakhir',
            'Status Pegawai',
            'Status Pernikahan',
            'Alamat',
            'TMT CPNS',
            'TMT PNS',
            'Unit Kerja',
            'SKPD',
            'Nama Jabatan',
            'Pangkat/Golongan',
            'BUP',
            'Status Kedudukan',
            'Status Workflow',
            'Diinput Oleh',
            'Tanggal Input',
        ];
    }

    protected $rowNumber = 0;

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->nip,
            $row->nama_lengkap,
            $row->nik ?? '-',
            $row->tempat_lahir,
            $row->tanggal_lahir ? $row->tanggal_lahir->format('d-m-Y') : '-',
            $row->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
            $row->agama,
            $row->pendidikan_terakhir,
            $row->status_pegawai,
            $row->status_pernikahan ?? '-',
            $row->alamat ?? '-',
            $row->tmt_cpns ? $row->tmt_cpns->format('d-m-Y') : '-',
            $row->tmt_pns ? $row->tmt_pns->format('d-m-Y') : '-',
            $row->unit_kerja ?? '-',
            $row->skpd ?? '-',
            $row->bezetting ? $row->bezetting->nama_jabatan : '-',
            $row->pangkat_golongan ?? '-',
            $row->bup,
            $row->status_kedudukan,
            $row->status,
            $row->creator ? $row->creator->name : 'Sistem',
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
