<?php

namespace Modules\Pemberdayaan\App\Services\Imports;

class NilaiTransaksiEkonomiGroupKey
{
    public static function make(
        ?int $cdkId,
        int $year,
        int $month,
        string $namaKth,
        ?int $provinceId,
        int $regencyId,
        int $districtId,
        int $villageId,
    ): string {
        $name = preg_replace('/\s+/u', ' ', trim($namaKth)) ?? trim($namaKth);

        return json_encode([
            $cdkId,
            $year,
            $month,
            mb_strtolower($name),
            $provinceId,
            $regencyId,
            $districtId,
            $villageId,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
