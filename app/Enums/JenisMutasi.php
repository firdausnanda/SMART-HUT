<?php

namespace App\Enums;

enum JenisMutasi: string
{
    case MUTASI_KELUAR_OPD = 'Mutasi Keluar OPD';
    case MENGUNDURKAN_DIRI = 'Mengundurkan Diri';
    case DIBERHENTIKAN = 'Diberhentikan';
}