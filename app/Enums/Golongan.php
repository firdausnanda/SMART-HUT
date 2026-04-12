<?php

namespace App\Enums;

enum Golongan: string
{
    case IA = 'I/a - Juru Muda';
    case IB = 'I/b - Juru Muda Tingkat I';
    case IC = 'I/c - Juru';
    case ID = 'I/d - Juru Tingkat I';
    case IIA = 'II/a - Pengatur Muda';
    case IIB = 'II/b - Pengatur Muda Tingkat I';
    case IIC = 'II/c - Pengatur';
    case IID = 'II/d - Pengatur Tingkat I';
    case IIIA = 'III/a - Penata Muda';
    case IIIB = 'III/b - Penata Muda Tingkat I';
    case IIIC = 'III/c - Penata';
    case IIID = 'III/d - Penata Tingkat I';
    case IVA = 'IV/a - Pembina';
    case IVB = 'IV/b - Pembina Tingkat I';
    case IVC = 'IV/c - Pembina Utama Muda';
    case IVD = 'IV/d - Pembina Utama Madya';
    case IVE = 'IV/e - Pembina Utama';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
