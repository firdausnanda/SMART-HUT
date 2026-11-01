<?php

namespace Modules\BinaUsaha\App\Http\Resources\Api\V1;

/**
 * Hasil hutan menyimpan pengelola di dua kolom: pengelola_hutan_id (Hutan
 * Negara, mis. BKPH/KPH) dan pengelola_wisata_id (Perhutanan Sosial, mis.
 * KTH). API mengirim satu field "pengelola" dari kolom yang terisi.
 */
trait FormatsPengelolaHasilHutan
{
    protected function pengelola(): ?array
    {
        return $this->ref($this->whenLoadedModel('pengelolaHutan'))
            ?? $this->ref($this->whenLoadedModel('pengelolaWisata'));
    }
}
