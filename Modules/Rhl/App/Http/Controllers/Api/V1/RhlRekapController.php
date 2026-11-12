<?php

namespace Modules\Rhl\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationRekapController;
use App\Models\PenghijauanLingkungan;
use App\Models\ReboisasiPS;
use App\Models\RehabLahan;
use App\Models\RehabManggrove;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rekap RHL berbasis luas untuk klien integrasi (ability rhl.read).
 */
class RhlRekapController extends IntegrationRekapController
{
    private const DATASETS = [
        'rehab_lahan' => RehabLahan::class,
        'penghijauan_lingkungan' => PenghijauanLingkungan::class,
        'rehab_mangrove' => RehabManggrove::class,
        'reboisasi_ps' => ReboisasiPS::class,
    ];

    public function show(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $models = array_values(self::DATASETS);

        $tahunTersedia = $this->tahunTersedia($request, $models);
        $tahun = isset($filters['tahun']) ? (int) $filters['tahun'] : (end($tahunTersedia) ?: null);

        $data = ['tahun' => $tahun, 'tahun_tersedia' => $tahunTersedia];
        foreach (self::DATASETS as $key => $model) {
            $rows = $this->rows($request, $model, ['month', 'regency_id', 'target_annual', 'realization'], $tahun, $filters['kabupaten_id'] ?? null);
            $data[$key] = [
                ...$this->ringkas($rows),
                'periode' => $this->periode($rows),
                'per_kabupaten' => collect($this->kabupaten($rows->pluck('regency_id')))->map(fn (array $ref, string $id) => [
                    'kabupaten' => $ref,
                    ...$this->ringkas($rows->filter(fn ($row) => (string) $row->regency_id === $id)),
                ])->values()->all(),
            ];
        }
        $data['diperbarui_pada'] = $this->diperbaruiPada($request, $models);

        return $this->respond($data);
    }

    /**
     * Satu baris = satu lokasi. Target tahunan tidak berulang per bulan,
     * sehingga boleh dijumlahkan langsung.
     */
    private function ringkas(Collection $rows): array
    {
        $target = round((float) $rows->sum('target_annual'), 2);
        $realisasi = round((float) $rows->sum('realization'), 2);

        return [
            'jumlah_lokasi' => $rows->count(),
            'target_tahunan_ha' => $target,
            'realisasi_ha' => $realisasi,
            // Null bila target belum diisi, supaya klien tidak menampilkan persentase.
            'persentase_realisasi' => $target > 0 ? round($realisasi / $target * 100, 1) : null,
        ];
    }
}
