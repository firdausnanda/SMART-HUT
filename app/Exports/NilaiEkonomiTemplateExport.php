<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiEkonomiTemplateExport implements FromArray, WithHeadings, WithStyles, \Maatwebsite\Excel\Concerns\WithEvents
{
  public function array(): array
  {
    // Example data
    return [
      [
        'tahun' => date('Y'),
        'bulan_1_12' => 1,
        'nama_kabupaten' => 'Madiun',
        'nama_kecamatan' => 'Dolopo',
        'nama_kelompok' => 'KTH Maju Bersama',
        'komoditas' => 'Madu, Getah Pinus',
        'volume_produksi' => '100, 50',
        'satuan' => 'Liter, Kg',
        'nilai_transaksi_rp' => '5000000, 2000000',
      ]
    ];
  }

  public function headings(): array
  {
    return [
      'Tahun',
      'Bulan (1-12)',
      'Nama Kabupaten',
      'Nama Kecamatan',
      'Nama Kelompok',
      'Komoditas',
      'Volume Produksi',
      'Satuan',
      'Nilai Transaksi (Rp)',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }

  public function registerEvents(): array
  {
    return [
      \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        $sheet->getComment('F1')->getText()->createTextRun('Bisa diisi lebih dari satu (pisahkan dengan koma). Contoh: Kopi, Madu');
        $sheet->getComment('G1')->getText()->createTextRun('Bisa diisi lebih dari satu (pisahkan dengan koma). Sesuaikan urutan dengan kolom Komoditas. Gunakan TITIK (.) untuk desimal. Contoh: 10, 5, 20.5');
        $sheet->getComment('H1')->getText()->createTextRun('Bisa diisi lebih dari satu (pisahkan dengan koma). Contoh: Kg, Liter, Kg');
        $sheet->getComment('I1')->getText()->createTextRun('Bisa diisi lebih dari satu (pisahkan dengan koma). Gunakan TITIK (.) untuk desimal atau ribuan tanpa pemisah. Contoh: 100000, 50000');
      },
    ];
  }
}

