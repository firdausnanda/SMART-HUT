<?php

namespace App\Enums;

enum StatusPegawai: string
{
    case PNS = 'PNS';
    case CPNS = 'CPNS';
    case PPPK = 'PPPK';
    case PPPKPW = 'PPPK Paruh Waktu';
    case PTT = 'PTT';
    case HONORER = 'Honorer';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}