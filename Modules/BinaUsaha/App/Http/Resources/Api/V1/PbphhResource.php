<?php

namespace Modules\BinaUsaha\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Pemegang izin PBPHH (industri pengolahan hasil hutan). Data ini daftar
 * izin, bukan laporan berkala, sehingga tidak ada tahun/bulan.
 */
class PbphhResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province'),
                $this->whenLoadedModel('regency'),
                $this->whenLoadedModel('district'),
                withPeriod: false,
            ),
            'nama' => $this->name,
            'nomor_izin' => $this->number,
            'nilai_investasi_rp' => $this->parseInteger($this->investment_value),
            'jumlah_tenaga_kerja' => (int) $this->number_of_workers,
            'beroperasi' => (bool) $this->present_condition,
            'jenis_produksi' => $this->jenisProduksi->map(fn ($jenis) => [
                'jenis' => $this->ref($jenis),
                'kapasitas_izin' => $jenis->pivot->kapasitas_ijin === null ? null : $this->decimal($jenis->pivot->kapasitas_ijin),
            ])->values(),
            ...$this->timestamps(),
        ];
    }
}
