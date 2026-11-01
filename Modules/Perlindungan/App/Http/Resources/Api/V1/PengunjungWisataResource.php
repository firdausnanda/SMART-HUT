<?php

namespace Modules\Perlindungan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Pengunjung dan pendapatan objek wisata alam per pengelola dan bulan.
 * Tabel ini tidak menyimpan lokasi, sehingga tidak ada field wilayah.
 */
class PengunjungWisataResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(withWilayah: false),
            'pengelola' => $this->ref($this->whenLoadedModel('pengelolaWisata')),
            'jumlah_pengunjung' => $this->parseInteger($this->number_of_visitors),
            'pendapatan_bruto_rp' => $this->parseInteger($this->gross_income),
            ...$this->timestamps(),
        ];
    }
}
