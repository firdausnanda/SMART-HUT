<?php

namespace Modules\Pemberdayaan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Nilai ekonomi per kelompok dan bulan, dengan rincian per komoditas.
 */
class NilaiEkonomiResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
            ),
            'nama_kelompok' => $this->nama_kelompok,
            'total_nilai_transaksi_rp' => $this->decimal($this->total_transaction_value),
            'rincian' => $this->details->map(fn ($detail) => [
                'komoditas' => $this->ref($detail->commodity),
                'volume_produksi' => $this->decimal($detail->production_volume),
                'satuan' => $detail->satuan,
                'nilai_transaksi_rp' => $this->decimal($detail->transaction_value),
            ])->values(),
            ...$this->timestamps(),
        ];
    }
}
