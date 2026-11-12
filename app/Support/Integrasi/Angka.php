<?php

namespace App\Support\Integrasi;

/**
 * Pembaca angka dari kolom teks yang diinput bebas. Dipakai API Resource dan
 * endpoint rekap agar nilai per baris dan nilai rekap selalu dibaca sama.
 */
class Angka
{
    /**
     * Angka desimal dari teks, mis. "1,5", "1.5", "1.250,75". Null bila kosong
     * atau tidak bisa dibaca.
     */
    public static function desimal($value): ?float
    {
        if ($value === null || is_int($value) || is_float($value)) {
            return $value === null ? null : round((float) $value, 2);
        }

        $text = preg_replace('/[^0-9.,-]/', '', (string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }

        if (str_contains($text, ',') && str_contains($text, '.')) {
            // Format Indonesia: titik ribuan, koma desimal.
            $text = str_replace(['.', ','], ['', '.'], $text);
        } elseif (str_contains($text, ',')) {
            $text = str_replace(',', '.', $text);
        } elseif (substr_count($text, '.') > 1) {
            $text = str_replace('.', '', $text);
        }

        return is_numeric($text) ? round((float) $text, 2) : null;
    }

    /**
     * Bilangan bulat dari teks, mis. "2120" atau "Rp 21.200.000".
     */
    public static function bulat($value): ?int
    {
        if ($value === null || is_int($value)) {
            return $value;
        }

        $text = trim((string) $value);
        // "2.120" atau "1,250,000" = pemisah ribuan, bukan desimal.
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $text)) {
            return (int) preg_replace('/[.,]/', '', $text);
        }
        if (is_numeric($text)) {
            return (int) round((float) $text);
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
    }
}
