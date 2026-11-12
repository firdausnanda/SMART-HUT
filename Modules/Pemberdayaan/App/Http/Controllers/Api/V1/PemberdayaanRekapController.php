<?php

namespace Modules\Pemberdayaan\App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\IntegrationRekapController;
use App\Models\Kups;
use App\Models\NilaiEkonomi;
use App\Models\NilaiTransaksiEkonomi;
use App\Models\PerkembanganKth;
use App\Models\Skps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rekap Pemberdayaan untuk klien integrasi (ability pemberdayaan.read).
 */
class PemberdayaanRekapController extends IntegrationRekapController
{
    public function show(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $kabupatenId = $filters['kabupaten_id'] ?? null;

        $tahunTersedia = $this->tahunTersedia($request, [NilaiTransaksiEkonomi::class, NilaiEkonomi::class]);
        $tahun = isset($filters['tahun']) ? (int) $filters['tahun'] : (end($tahunTersedia) ?: null);

        // perkembangan_kth adalah riwayat: satu KTH bisa punya beberapa catatan.
        // Filter kabupaten diterapkan setelah memilih catatan terbaru per KTH.
        $catatanKth = $this->rows($request, PerkembanganKth::class, ['id', 'nomor_register', 'year', 'month', 'regency_id', 'jumlah_anggota', 'luas_kelola']);
        $kth = $this->kthTerbaru($catatanKth);
        if ($kabupatenId) {
            $catatanKth = $catatanKth->filter(fn ($row) => (string) $row->regency_id === $kabupatenId);
            $kth = $kth->filter(fn ($row) => (string) $row->regency_id === $kabupatenId);
        }

        $skps = $this->rows($request, Skps::class, ['regency_id', 'ps_area', 'number_of_kk'], false, $kabupatenId);
        $kups = $this->rows($request, Kups::class, ['category'], false, $kabupatenId);
        $transaksi = $this->rows($request, NilaiTransaksiEkonomi::class, ['month', 'regency_id', 'total_nilai_transaksi as nilai'], $tahun, $kabupatenId);
        $ekonomi = $this->rows($request, NilaiEkonomi::class, ['month', 'regency_id', 'total_transaction_value as nilai'], $tahun, $kabupatenId);

        $kabupaten = $this->kabupaten([...$kth->pluck('regency_id'), ...$skps->pluck('regency_id'), ...$transaksi->pluck('regency_id')]);

        return $this->respond([
            'tahun' => $tahun,
            'tahun_tersedia' => $tahunTersedia,
            'kth' => [...$this->ringkasKth($kth), 'jumlah_catatan' => $catatanKth->count()],
            'perhutanan_sosial' => $this->ringkasSkps($skps),
            'kups' => [
                'jumlah' => $kups->count(),
                'per_kelas' => $kups->countBy('category')->sortKeys()
                    ->map(fn (int $jumlah, string $kelas) => ['kelas' => $kelas, 'jumlah' => $jumlah])->values()->all(),
            ],
            'nilai_transaksi_ekonomi' => $this->ringkasNilai($transaksi),
            'nilai_ekonomi' => $this->ringkasNilai($ekonomi),
            'per_kabupaten' => collect($kabupaten)->map(fn (array $ref, string $id) => [
                'kabupaten' => $ref,
                'kth' => $this->ringkasKth($kth->filter(fn ($row) => (string) $row->regency_id === $id)),
                'perhutanan_sosial' => $this->ringkasSkps($skps->filter(fn ($row) => (string) $row->regency_id === $id)),
                'nilai_transaksi_ekonomi_rp' => round((float) $transaksi->filter(fn ($row) => (string) $row->regency_id === $id)->sum('nilai'), 2),
            ])->values()->all(),
            'diperbarui_pada' => $this->diperbaruiPada($request, [PerkembanganKth::class, Skps::class, Kups::class, NilaiTransaksiEkonomi::class, NilaiEkonomi::class]),
        ]);
    }

    /**
     * Satu catatan per KTH: catatan terbaru (tahun, bulan, lalu id terbesar)
     * untuk setiap nomor register. Catatan tanpa nomor register dihitung
     * sebagai KTH tersendiri.
     */
    private function kthTerbaru(Collection $catatan): Collection
    {
        return $catatan
            ->groupBy(function ($row) {
                $register = strtoupper(preg_replace('/\s+/', '', (string) $row->nomor_register));

                return $register !== '' ? $register : 'id:' . $row->id;
            })
            ->map(fn (Collection $group) => $group->sortBy([['year', 'desc'], ['month', 'desc'], ['id', 'desc']])->first())
            ->values();
    }

    private function ringkasKth(Collection $kth): array
    {
        return [
            'jumlah' => $kth->count(),
            'jumlah_anggota' => (int) $kth->sum('jumlah_anggota'),
            'luas_kelola_ha' => round((float) $kth->sum('luas_kelola'), 2),
        ];
    }

    private function ringkasSkps(Collection $skps): array
    {
        return [
            'jumlah_kelompok' => $skps->count(),
            'jumlah_kk' => (int) $skps->sum('number_of_kk'),
            'luas_ps_ha' => round((float) $skps->sum('ps_area'), 2),
        ];
    }

    private function ringkasNilai(Collection $rows): array
    {
        return [
            'total_rp' => round((float) $rows->sum('nilai'), 2),
            'jumlah_catatan' => $rows->count(),
            'periode' => $this->periode($rows),
            'per_bulan' => $rows->groupBy(fn ($row) => (int) $row->month)->sortKeys()
                ->map(fn (Collection $group, int $bulan) => [
                    'bulan' => $bulan,
                    'jumlah_catatan' => $group->count(),
                    'total_rp' => round((float) $group->sum('nilai'), 2),
                ])->values()->all(),
        ];
    }
}
