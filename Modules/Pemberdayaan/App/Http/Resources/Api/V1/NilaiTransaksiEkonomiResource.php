<?php

namespace Modules\Pemberdayaan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Nilai transaksi ekonomi KTH per bulan, dengan rincian per komoditas.
 */
class NilaiTransaksiEkonomiResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province_rel'),
                $this->whenLoadedModel('regency_rel'),
                $this->whenLoadedModel('district_rel'),
                $this->whenLoadedModel('village_rel'),
            ),
            'nama_kth' => $this->nama_kth,
            'total_nilai_transaksi_rp' => $this->decimal($this->total_nilai_transaksi),
            'rincian' => $this->details->map(fn ($detail) => [
                'komoditas' => $this->ref($detail->commodity),
                'volume_produksi' => $this->decimal($detail->volume_produksi),
                'satuan' => $detail->satuan,
                'nilai_transaksi_rp' => $this->decimal($detail->nilai_transaksi),
            ])->values(),
            ...$this->timestamps(),
        ];
    }
}
