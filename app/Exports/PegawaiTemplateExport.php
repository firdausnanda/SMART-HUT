<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PegawaiTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'Template Import Pegawai';
    }

    public function headings(): array
    {
        return [
            'nip*',
            'nama_lengkap*',
            'nik',
            'tempat_lahir*',
            'tanggal_lahir* (YYYY-MM-DD)',
            'jenis_kelamin* (L/P)',
            'agama* (Islam/Kristen/Katolik/Hindu/Budha/Konghucu)',
            'pendidikan_terakhir*',
            'status_pegawai* (PNS/CPNS/PPPK/PTT/Honorer)',
            'status_pernikahan (Belum Kawin/Kawin/Cerai Hidup/Cerai Mati)',
            'alamat',
            'tmt_cpns (YYYY-MM-DD)',
            'tmt_pns (YYYY-MM-DD)',
            'unit_kerja',
            'skpd',
            'nama_jabatan (sesuai data jabatan)',
            'pangkat_golongan',
            'bup* (angka tahun, misal: 60)',
            'status_kedudukan* (Aktif/Pensiun/Mutasi/Meninggal)',
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add example data row
                $sheet->fromArray([
                    [
                        '199001012015011001',
                        'Contoh Nama Lengkap',
                        '3578123456789001',
                        'Surabaya',
                        '1990-01-01',
                        'L',
                        'Islam',
                        'S1',
                        'PNS',
                        'Kawin',
                        'Jl. Contoh No. 1',
                        '2010-01-01',
                        '2015-01-01',
                        'SEKSI REHABILITASI LAHAN DAN PEMBERDAYAAN MASYARAKAT',
                        'DINAS KEHUTANAN',
                        '',
                        'III/a - Penata Muda',
                        '58',
                        'Aktif',
                    ]
                ], null, 'A2');

                // Style example row
                $sheet->getStyle('A2:S2')->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF9C3']],
                    'font' => ['italic' => true, 'color' => ['rgb' => '92400E']],
                ]);

                // Add note about example row
                $sheet->getComment('A2')->getText()->createTextRun('Baris ini adalah contoh. Hapus sebelum melakukan import.');

                // Dropdown: Jenis Kelamin (column F)
                $jenisKelaminValidation = $sheet->getCell('F2')->getDataValidation();
                $jenisKelaminValidation->setType(DataValidation::TYPE_LIST);
                $jenisKelaminValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $jenisKelaminValidation->setAllowBlank(false);
                $jenisKelaminValidation->setShowDropDown(false);
                $jenisKelaminValidation->setFormula1('"L,P"');
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell("F{$i}")->setDataValidation(clone $jenisKelaminValidation);
                }

                // Dropdown: Agama (column G)
                $agamaValidation = $sheet->getCell('G2')->getDataValidation();
                $agamaValidation->setType(DataValidation::TYPE_LIST);
                $agamaValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $agamaValidation->setAllowBlank(false);
                $agamaValidation->setShowDropDown(false);
                $agamaValidation->setFormula1('"Islam,Kristen,Katolik,Hindu,Budha,Konghucu"');
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell("G{$i}")->setDataValidation(clone $agamaValidation);
                }

                // Dropdown: Status Pegawai (column I)
                $statusPegawaiValidation = $sheet->getCell('I2')->getDataValidation();
                $statusPegawaiValidation->setType(DataValidation::TYPE_LIST);
                $statusPegawaiValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $statusPegawaiValidation->setAllowBlank(false);
                $statusPegawaiValidation->setShowDropDown(false);
                $statusPegawaiValidation->setFormula1('"PNS,CPNS,PPPK,PTT,Honorer"');
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell("I{$i}")->setDataValidation(clone $statusPegawaiValidation);
                }

                // Dropdown: Status Pernikahan (column J)
                $statusPernikahanValidation = $sheet->getCell('J2')->getDataValidation();
                $statusPernikahanValidation->setType(DataValidation::TYPE_LIST);
                $statusPernikahanValidation->setAllowBlank(true);
                $statusPernikahanValidation->setShowDropDown(false);
                $statusPernikahanValidation->setFormula1('"Belum Kawin,Kawin,Cerai Hidup,Cerai Mati"');
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell("J{$i}")->setDataValidation(clone $statusPernikahanValidation);
                }

                // Dropdown: Status Kedudukan (column S)
                $statusKedudukanValidation = $sheet->getCell('S2')->getDataValidation();
                $statusKedudukanValidation->setType(DataValidation::TYPE_LIST);
                $statusKedudukanValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $statusKedudukanValidation->setAllowBlank(false);
                $statusKedudukanValidation->setShowDropDown(false);
                $statusKedudukanValidation->setFormula1('"Aktif,Pensiun,Mutasi,Meninggal"');
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell("S{$i}")->setDataValidation(clone $statusKedudukanValidation);
                }
            },
        ];
    }
}
