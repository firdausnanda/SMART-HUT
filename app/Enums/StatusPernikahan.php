<?php

namespace App\Enums;

enum StatusPernikahan: string
{
    case BELUM_KAWIN = 'Belum Kawin';
    case KAWIN = 'Kawin';
    case CERAI_HIDUP = 'Cerai Hidup';
    case CERAI_MATI = 'Cerai Mati';

    public function label(): string
    {
        return $this->value;
    }

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label()
        ], self::cases());
    }
}
