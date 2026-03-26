<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProyeksiExport implements WithMultipleSheets
{
    protected $kgbData;
    protected $pensiunData;

    public function __construct($kgbData, $pensiunData)
    {
        $this->kgbData = $kgbData;
        $this->pensiunData = $pensiunData;
    }

    public function sheets(): array
    {
        return [
            new KgbExport($this->kgbData),
            new PensiunExport($this->pensiunData),
        ];
    }
}
