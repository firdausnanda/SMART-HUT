<?php

namespace Modules\Perlindungan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Kejadian kebakaran hutan per lokasi dan bulan.
 */
class KebakaranHutanResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
                $this->whenLoadedModel('village'),
            ),
            // Kolom id_pengelola_wisata pada tabel ini berisi pengelola kawasan hutan.
            'pengelola' => $this->ref($this->whenLoadedModel('pengelolaWisata')),
            'fungsi_kawasan' => $this->area_function,
            'jumlah_kejadian' => (int) $this->number_of_fires,
            'luas_terbakar_ha' => $this->parseDecimal($this->fire_area),
            'koordinat' => $this->coordinates,
            ...$this->timestamps(),
        ];
    }
}
