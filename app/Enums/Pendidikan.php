<?php

namespace App\Enums;

enum Pendidikan: string
{
    case SD = 'SD';
    case SMP = 'SMP';
    case SMA = 'SMA';
    case SMK = 'SMK';
    case D1 = 'D-I';
    case D2 = 'D-II';
    case D3 = 'D-III';
    case D4 = 'D-IV';
    case S1 = 'S-1';
    case S2 = 'S-2';
    case S3 = 'S-3';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
