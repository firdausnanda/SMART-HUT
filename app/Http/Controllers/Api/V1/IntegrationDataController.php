<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IntegrationClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Dasar endpoint data /api/v1 per modul.
 *
 * Turunan cukup mendefinisikan datasets(): kunci dataset (dari default rute
 * "dataset") => model, relasi yang dimuat, dan kelas API Resource. Semua
 * dataset otomatis mendapat filter, paginasi, sinkronisasi inkremental
 * (updated_since) dan daftar data yang ditarik (deleted).
 */
abstract class IntegrationDataController extends Controller
{
    public const MAX_PER_PAGE = 500;
    public const DEFAULT_PER_PAGE = 100;

    /**
     * Opsi per dataset: "regency_column" => null bila tabel tidak punya kolom
     * kabupaten (filter kabupaten_id lalu ditolak), bawaan "regency_id";
     * "period" => false bila tabel tidak punya year/month (filter tahun/bulan ditolak).
     *
     * @return array<string, array{model: class-string<\Illuminate\Database\Eloquent\Model>, with: array, resource: class-string<\Illuminate\Http\Resources\Json\JsonResource>, regency_column?: string|null, period?: bool}>
     */
    abstract protected function datasets(): array;

    public function index(Request $request): JsonResponse
    {
        $dataset = $this->dataset($request);
        $regencyColumn = array_key_exists('regency_column', $dataset) ? $dataset['regency_column'] : 'regency_id';
        $periodic = $dataset['period'] ?? true;
        $filters = $request->validate([
            'tahun' => $periodic ? ['nullable', 'integer', 'between:2000,2100'] : ['prohibited'],
            'bulan' => $periodic ? ['nullable', 'integer', 'between:1,12'] : ['prohibited'],
            'kabupaten_id' => $regencyColumn ? ['nullable', 'string', 'max:4'] : ['prohibited'],
            'cdk_id' => ['nullable', 'integer'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'between:1,' . self::MAX_PER_PAGE],
        ], [
            'kabupaten_id.prohibited' => 'Data ini tidak memiliki kolom wilayah, sehingga tidak bisa difilter per kabupaten.',
            'tahun.prohibited' => 'Data ini tidak memiliki periode, sehingga tidak bisa difilter per tahun.',
            'bulan.prohibited' => 'Data ini tidak memiliki periode, sehingga tidak bisa difilter per bulan.',
        ]);

        $model = new $dataset['model'];
        $table = $model->getTable();
        $query = $this->client($request)->applyDataScope($model->newQuery())->with($dataset['with']);

        $query
            ->when($filters['tahun'] ?? null, fn (Builder $q, $tahun) => $q->where("$table.year", $tahun))
            ->when($filters['bulan'] ?? null, fn (Builder $q, $bulan) => $q->where("$table.month", $bulan))
            ->when($filters['kabupaten_id'] ?? null, fn (Builder $q, $id) => $q->where("$table.$regencyColumn", $id))
            ->when($filters['cdk_id'] ?? null, fn (Builder $q, $id) => $q->where("$table.cdk_id", $id))
            ->when($filters['updated_since'] ?? null, fn (Builder $q, $since) => $q->where("$table.updated_at", '>=', $this->toAppTime($since)))
            // Urutan stabil untuk sinkronisasi: data yang berubah selama paginasi pindah ke akhir.
            ->orderBy("$table.updated_at")
            ->orderBy("$table.id");

        $page = $query->paginate($filters['per_page'] ?? self::DEFAULT_PER_PAGE)->withQueryString();

        return $this->paginated($page, $dataset['resource']::collection($page->getCollection())->resolve($request));
    }

    /**
     * ID data yang sejak waktu tertentu tidak lagi dikirim API: dihapus, atau
     * statusnya tidak final lagi (mis. ditolak ulang). Klien yang menyimpan
     * salinan data wajib menghapus ID ini dari salinannya.
     */
    public function deleted(Request $request): JsonResponse
    {
        $dataset = $this->dataset($request);
        $filters = $request->validate([
            'since' => ['required', 'date'],
            'per_page' => ['nullable', 'integer', 'between:1,' . self::MAX_PER_PAGE * 2],
        ]);

        $since = $this->toAppTime($filters['since']);
        $model = new $dataset['model'];
        $table = $model->getTable();

        $page = $this->client($request)
            ->applyCdkScope($model->newQuery()->withTrashed())
            ->where(fn (Builder $q) => $q
                ->where("$table.deleted_at", '>=', $since)
                ->orWhere(fn (Builder $q) => $q
                    ->where("$table.status", '!=', 'final')
                    ->where("$table.updated_at", '>=', $since)))
            ->orderBy("$table.id")
            ->paginate($filters['per_page'] ?? self::MAX_PER_PAGE, ["$table.id", "$table.status", "$table.updated_at", "$table.deleted_at"])
            ->withQueryString();

        return $this->paginated($page, $page->getCollection()->map(fn ($row) => [
            'id' => $row->id,
            'alasan' => $row->deleted_at ? 'dihapus' : 'tidak_final',
            'waktu' => ($row->deleted_at ?? $row->updated_at)?->toIso8601String(),
        ])->values()->all());
    }

    /**
     * Bentuk respons daftar yang sama untuk semua endpoint data.
     */
    private function paginated(LengthAwarePaginator $page, array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'links' => [
                'next' => $page->nextPageUrl(),
                'prev' => $page->previousPageUrl(),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'waktu_server' => now()->toIso8601String(),
        ]);
    }

    private function dataset(Request $request): array
    {
        $key = $request->route('dataset');

        return $this->datasets()[$key] ?? abort(404);
    }

    private function client(Request $request): IntegrationClient
    {
        return $request->user();
    }

    private function toAppTime(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));
    }
}
