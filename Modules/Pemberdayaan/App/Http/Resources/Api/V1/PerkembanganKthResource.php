<?php

namespace Modules\Pemberdayaan\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Perkembangan Kelompok Tani Hutan per periode laporan.
 */
class PerkembanganKthResource extends IntegrationResource
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
            'nomor_register' => $this->nomor_register,
            'kelas_kelembagaan' => $this->kelas_kelembagaan,
            'jumlah_anggota' => (int) $this->jumlah_anggota,
            'luas_kelola_ha' => $this->decimal($this->luas_kelola),
            'potensi_kawasan' => $this->potensi_kawasan,
            ...$this->timestamps(),
        ];
    }
}
