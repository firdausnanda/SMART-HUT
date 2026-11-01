<?php

namespace Modules\BinaUsaha\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Produksi hasil hutan bukan kayu per lokasi dan bulan. Satuan berbeda per
 * komoditas, sehingga realisasi hanya dikirim per rincian, tidak dijumlahkan.
 */
class HasilHutanBukanKayuResource extends IntegrationResource
{
    use FormatsPengelolaHasilHutan;

    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
            ),
            'jenis_hutan' => $this->forest_type,
            'pengelola' => $this->pengelola(),
            'target_volume' => $this->decimal($this->volume_target),
            'rincian' => $this->details->map(fn ($detail) => [
                'komoditas' => $this->ref($detail->bukanKayu),
                'realisasi_volume' => $this->decimal($detail->annual_volume_realization),
                // Nilai mentah, bukan enum: data lama bisa berisi satuan di luar enum Satuan.
                'satuan' => $detail->getRawOriginal('unit'),
            ])->values(),
            ...$this->timestamps(),
        ];
    }
}
