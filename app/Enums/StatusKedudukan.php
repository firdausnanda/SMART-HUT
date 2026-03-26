<?php

namespace App\Enums;

enum StatusKedudukan: string
{
    case AKTIF = 'Aktif';
    case CUTI = 'Cuti';
    case TUGAS_BELAJAR = 'Tugas Belajar';
    case PENSIUN = 'Pensiun';
    case DIBERHENTIKAN = 'Diberhentikan';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
