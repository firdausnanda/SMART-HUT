<?php

namespace App\Enums;

enum Agama: string
{
    case ISLAM = 'Islam';
    case KRISTEN_PROTESTAN = 'Kristen Protestan';
    case KATOLIK = 'Katolik';
    case HINDU = 'Hindu';
    case BUDDHA = 'Buddha';
    case KONGHUCU = 'Konghucu';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
