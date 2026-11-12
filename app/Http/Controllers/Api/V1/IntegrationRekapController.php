<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Regencies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Dasar endpoint rekap /api/v1/{modul}/rekap: angka ringkasan yang dihitung di
 * server dari data final, dengan cakupan CDK yang sama seperti endpoint data.
 *
 * Tahun bawaan adalah tahun terakhir yang punya data final, bukan tahun
 * kalender, supaya rekap tidak kosong di awal tahun.
 */
abstract class IntegrationRekapController extends Controller
{
    /**
     * @return array{tahun?: int|null, kabupaten_id?: string|null}
     */
    protected function filters(Request $request): array
    {
        return $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'kabupaten_id' => ['nullable', 'string', 'max:4'],
        ]);
    }

    /**
     * Query data final milik klien (dan CDK-nya bila klien terikat CDK).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function scoped(Request $request, string $model): Builder
    {
        return $request->user()->applyDataScope((new $model)->newQuery());
    }

    /**
     * Baris mentah (stdClass) untuk dijumlahkan di PHP. $tahun false = dataset
     * tanpa periode; null = belum ada data sama sekali.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function rows(Request $request, string $model, array $columns, int|null|false $tahun = false, ?string $kabupatenId = null): Collection
    {
        if ($tahun === null) {
            return collect();
        }

        $query = $this->scoped($request, $model);
        $table = $query->getModel()->getTable();

        return $query
            ->when($tahun !== false, fn (Builder $q) => $q->where("$table.year", $tahun))
            ->when($kabupatenId, fn (Builder $q) => $q->where("$table.regency_id", $kabupatenId))
            ->toBase()
            ->get($columns);
    }

    /**
     * Tahun yang punya data final pada salah satu model, urut naik.
     *
     * @param  array<class-string<\Illuminate\Database\Eloquent\Model>>  $models
     * @return int[]
     */
    protected function tahunTersedia(Request $request, array $models): array
    {
        return collect($models)
            ->flatMap(fn (string $model) => $this->scoped($request, $model)->toBase()->distinct()->pluck('year'))
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Waktu perubahan terakhir di antara semua model (ISO 8601), atau null.
     *
     * @param  array<class-string<\Illuminate\Database\Eloquent\Model>>  $models
     */
    protected function diperbaruiPada(Request $request, array $models): ?string
    {
        $latest = collect($models)
            ->map(fn (string $model) => $this->scoped($request, $model)->toBase()->max('updated_at'))
            ->filter()
            ->max();

        return $latest ? Carbon::parse($latest)->toIso8601String() : null;
    }

    /**
     * Rentang bulan yang punya data, atau null bila tidak ada baris.
     */
    protected function periode(Collection $rows): ?array
    {
        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'bulan_awal' => (int) $rows->min('month'),
            'bulan_akhir' => (int) $rows->max('month'),
        ];
    }

    /**
     * {id, nama} kabupaten untuk sekumpulan kode, dikunci dengan kode (string).
     *
     * @return array<string, array{id: string, nama: string}>
     */
    protected function kabupaten(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (string) $id)->unique()->values();
        $names = $ids->isEmpty() ? collect() : Regencies::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $ids
            ->sort()
            ->mapWithKeys(fn (string $id) => [$id => ['id' => $id, 'nama' => $names[$id] ?? null]])
            ->all();
    }

    protected function respond(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'waktu_server' => now()->toIso8601String(),
        ]);
    }
}
