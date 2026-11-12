<?php

namespace Modules\Perlindungan\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationRekapController;
use App\Models\KebakaranHutan;
use App\Models\PengelolaWisata;
use App\Models\PengunjungWisata;
use App\Support\Integrasi\Angka;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rekap Perlindungan untuk klien integrasi (ability perlindungan.read).
 */
class PerlindunganRekapController extends IntegrationRekapController
{
    public function show(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $kabupatenId = $filters['kabupaten_id'] ?? null;
        $models = [KebakaranHutan::class, PengunjungWisata::class];

        $tahunTersedia = $this->tahunTersedia($request, $models);
        $tahun = isset($filters['tahun']) ? (int) $filters['tahun'] : (end($tahunTersedia) ?: null);

        $kebakaran = $this->rows($request, KebakaranHutan::class, ['month', 'regency_id', 'number_of_fires', 'fire_area'], $tahun, $kabupatenId);

        return $this->respond([
            'tahun' => $tahun,
            'tahun_tersedia' => $tahunTersedia,
            'kebakaran_hutan' => [
                ...$this->ringkasKebakaran($kebakaran),
                'periode' => $this->periode($kebakaran),
                'per_kabupaten' => collect($this->kabupaten($kebakaran->pluck('regency_id')))->map(fn (array $ref, string $id) => [
                    'kabupaten' => $ref,
                    ...$this->ringkasKebakaran($kebakaran->filter(fn ($row) => (string) $row->regency_id === $id)),
                ])->values()->all(),
            ],
            // Tabel pengunjung wisata tidak menyimpan lokasi, sehingga tidak bisa
            // dipecah per kabupaten: null bila kabupaten_id dipakai.
            'pengunjung_wisata' => $kabupatenId ? null : $this->pengunjung(
                $this->rows($request, PengunjungWisata::class, ['month', 'id_pengelola_wisata', 'number_of_visitors', 'gross_income'], $tahun)
            ),
            'diperbarui_pada' => $this->diperbaruiPada($request, $models),
        ]);
    }

    private function ringkasKebakaran(Collection $rows): array
    {
        return [
            'jumlah_kejadian' => (int) $rows->sum('number_of_fires'),
            'luas_terbakar_ha' => round((float) $rows->sum(fn ($row) => Angka::desimal($row->fire_area) ?? 0), 2),
        ];
    }

    private function pengunjung(Collection $rows): array
    {
        $names = PengelolaWisata::query()->whereIn('id', $rows->pluck('id_pengelola_wisata')->unique())->pluck('name', 'id');

        return [
            ...$this->ringkasPengunjung($rows),
            'periode' => $this->periode($rows),
            // Daftar pengelola yang melapor, agar klien tahu seberapa lengkap angkanya.
            'per_pengelola' => $rows->groupBy('id_pengelola_wisata')
                ->map(fn (Collection $group, $id) => [
                    'pengelola' => ['id' => (int) $id, 'nama' => $names[$id] ?? null],
                    ...$this->ringkasPengunjung($group),
                    'periode' => $this->periode($group),
                ])
                ->sortByDesc('jumlah_pengunjung')
                ->values()
                ->all(),
        ];
    }

    private function ringkasPengunjung(Collection $rows): array
    {
        return [
            'jumlah_pengunjung' => (int) $rows->sum(fn ($row) => Angka::bulat($row->number_of_visitors) ?? 0),
            'pendapatan_bruto_rp' => (int) $rows->sum(fn ($row) => Angka::bulat($row->gross_income) ?? 0),
        ];
    }
}
