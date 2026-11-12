<?php

namespace Modules\BinaUsaha\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationRekapController;
use App\Models\BukanKayu;
use App\Models\HasilHutanBukanKayu;
use App\Models\HasilHutanBukanKayuDetail;
use App\Models\HasilHutanKayu;
use App\Models\HasilHutanKayuDetail;
use App\Models\Pbphh;
use App\Models\RealisasiPnbp;
use App\Support\Integrasi\Angka;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rekap Bina Usaha untuk klien integrasi (ability bina-usaha.read).
 */
class BinaUsahaRekapController extends IntegrationRekapController
{
    public function show(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $kabupatenId = $filters['kabupaten_id'] ?? null;
        $berkala = [HasilHutanKayu::class, HasilHutanBukanKayu::class, RealisasiPnbp::class];

        $tahunTersedia = $this->tahunTersedia($request, $berkala);
        $tahun = isset($filters['tahun']) ? (int) $filters['tahun'] : (end($tahunTersedia) ?: null);

        $kayu = $this->rows($request, HasilHutanKayu::class, ['id', 'month', 'regency_id', 'volume_target'], $tahun, $kabupatenId);
        $realisasiKayu = $this->details(HasilHutanKayuDetail::class, 'hasil_hutan_kayu_id', $kayu, ['volume_realization'])
            ->groupBy('parent_id')
            ->map(fn (Collection $group) => (float) $group->sum('volume_realization'));
        $kayu->each(fn ($row) => $row->realisasi = $realisasiKayu[$row->id] ?? 0.0);

        $bukanKayu = $this->rows($request, HasilHutanBukanKayu::class, ['id', 'month', 'volume_target'], $tahun, $kabupatenId);
        $pnbp = $this->rows($request, RealisasiPnbp::class, ['month', 'regency_id', 'pnbp_target', 'pnbp_realization'], $tahun, $kabupatenId);
        $pbphh = $this->rows($request, Pbphh::class, ['regency_id', 'investment_value', 'number_of_workers', 'present_condition'], false, $kabupatenId);

        return $this->respond([
            'tahun' => $tahun,
            'tahun_tersedia' => $tahunTersedia,
            'hasil_hutan_kayu' => [
                ...$this->ringkasKayu($kayu),
                'periode' => $this->periode($kayu),
                'per_kabupaten' => $this->perKabupaten($kayu, fn (Collection $rows) => $this->ringkasKayu($rows)),
            ],
            'hasil_hutan_bukan_kayu' => [
                'jumlah_catatan' => $bukanKayu->count(),
                'periode' => $this->periode($bukanKayu),
                'per_komoditas' => $this->perKomoditas($bukanKayu),
            ],
            'realisasi_pnbp' => [
                ...$this->ringkasPnbp($pnbp),
                'periode' => $this->periode($pnbp),
                'per_kabupaten' => $this->perKabupaten($pnbp, fn (Collection $rows) => $this->ringkasPnbp($rows)),
            ],
            'pbphh' => [
                ...$this->ringkasPbphh($pbphh),
                'per_kabupaten' => $this->perKabupaten($pbphh, fn (Collection $rows) => $this->ringkasPbphh($rows)),
            ],
            'diperbarui_pada' => $this->diperbaruiPada($request, [...$berkala, Pbphh::class]),
        ]);
    }

    /**
     * Baris rincian milik sekumpulan baris induk, dengan kunci induk sebagai parent_id.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function details(string $model, string $foreignKey, Collection $parents, array $columns): Collection
    {
        return $parents->pluck('id')->chunk(1000)->flatMap(
            fn (Collection $ids) => (new $model)->newQuery()->whereIn($foreignKey, $ids)->toBase()->get(["$foreignKey as parent_id", ...$columns])
        );
    }

    private function perKabupaten(Collection $rows, callable $ringkas): array
    {
        return collect($this->kabupaten($rows->pluck('regency_id')))->map(fn (array $ref, string $id) => [
            'kabupaten' => $ref,
            ...$ringkas($rows->filter(fn ($row) => (string) $row->regency_id === $id)),
        ])->values()->all();
    }

    private function persentase(float $realisasi, float $target): ?float
    {
        // Null bila target belum diisi, supaya klien tidak menampilkan persentase.
        return $target > 0 ? round($realisasi / $target * 100, 1) : null;
    }

    private function ringkasKayu(Collection $rows): array
    {
        $target = round((float) $rows->sum('volume_target'), 2);
        $realisasi = round((float) $rows->sum('realisasi'), 2);

        return [
            'jumlah_catatan' => $rows->count(),
            'target_volume_m3' => $target,
            'realisasi_volume_m3' => $realisasi,
            'persentase_realisasi' => $this->persentase($realisasi, $target),
        ];
    }

    /**
     * Satuan hasil hutan bukan kayu berbeda per komoditas, sehingga realisasi
     * dijumlahkan per komoditas dan satuan, tidak digabung.
     */
    private function perKomoditas(Collection $rows): array
    {
        $details = $this->details(HasilHutanBukanKayuDetail::class, 'hasil_hutan_bukan_kayu_id', $rows, ['bukan_kayu_id', 'annual_volume_realization', 'unit']);
        $names = $details->isEmpty() ? collect() : BukanKayu::query()->whereIn('id', $details->pluck('bukan_kayu_id')->unique())->pluck('name', 'id');

        return $details
            ->groupBy(fn ($detail) => $detail->bukan_kayu_id . '|' . strtolower(trim((string) $detail->unit)))
            ->map(fn (Collection $group) => [
                'komoditas' => ['id' => (int) $group->first()->bukan_kayu_id, 'nama' => $names[$group->first()->bukan_kayu_id] ?? null],
                'satuan' => strtolower(trim((string) $group->first()->unit)),
                'realisasi_volume' => round((float) $group->sum('annual_volume_realization'), 2),
            ])
            ->sortBy([['komoditas.nama', 'asc'], ['satuan', 'asc']])
            ->values()
            ->all();
    }

    private function ringkasPnbp(Collection $rows): array
    {
        $target = (int) $rows->sum(fn ($row) => Angka::bulat($row->pnbp_target) ?? 0);
        $realisasi = (int) $rows->sum(fn ($row) => Angka::bulat($row->pnbp_realization) ?? 0);

        return [
            'target_pnbp_rp' => $target,
            'realisasi_pnbp_rp' => $realisasi,
            'persentase_realisasi' => $this->persentase($realisasi, $target),
        ];
    }

    private function ringkasPbphh(Collection $rows): array
    {
        return [
            'jumlah_izin' => $rows->count(),
            'jumlah_beroperasi' => $rows->filter(fn ($row) => (bool) $row->present_condition)->count(),
            'jumlah_tenaga_kerja' => (int) $rows->sum('number_of_workers'),
            'nilai_investasi_rp' => (int) $rows->sum(fn ($row) => Angka::bulat($row->investment_value) ?? 0),
        ];
    }
}
