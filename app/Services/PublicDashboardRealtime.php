<?php

namespace App\Services;

use App\Events\PublicDashboardChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PublicDashboardRealtime
{
    private const MODEL_DOMAINS = [
        \App\Models\RehabLahan::class => 'pembinaan',
        \App\Models\PenghijauanLingkungan::class => 'pembinaan',
        \App\Models\RehabManggrove::class => 'pembinaan',
        \App\Models\RhlTeknis::class => 'pembinaan',
        \App\Models\ReboisasiPS::class => 'pembinaan',
        \App\Models\KebakaranHutan::class => 'perlindungan',
        \App\Models\PengunjungWisata::class => 'perlindungan',
        \App\Models\HasilHutanKayu::class => 'bina_usaha',
        \App\Models\HasilHutanBukanKayu::class => 'bina_usaha',
        \App\Models\Pbphh::class => 'bina_usaha',
        \App\Models\RealisasiPnbp::class => 'bina_usaha',
        \App\Models\Skps::class => 'kelembagaan_ps',
        \App\Models\NilaiEkonomi::class => 'kelembagaan_ps',
        \App\Models\PerkembanganKth::class => 'kelembagaan_hr',
        \App\Models\NilaiTransaksiEkonomi::class => 'kelembagaan_hr',
        \App\Models\RekapStatistikBulanan::class => 'kepegawaian',
        \App\Models\RekapBulananPegawai::class => 'kepegawaian',
    ];

    private const YEARLESS_MODELS = [
        \App\Models\Pbphh::class,
        \App\Models\Skps::class,
        \App\Models\PerkembanganKth::class,
    ];

    public static function observedModels(): array
    {
        return array_keys(self::MODEL_DOMAINS);
    }

    public function recordChange(Model $model, ?array $before, ?array $after): void
    {
        $domain = self::MODEL_DOMAINS[$model::class] ?? null;
        if ($domain === null) {
            return;
        }

        $yearField = $domain === 'kepegawaian' ? 'periode_tahun' : 'year';
        $yearless = in_array($model::class, self::YEARLESS_MODELS, true);
        $scopes = [];
        foreach ([$before, $after] as $attributes) {
            if ($attributes === null || ($attributes['status'] ?? null) !== 'final') {
                continue;
            }
            $year = $yearless ? null : (int) ($attributes[$yearField] ?? 0);
            $cdkId = isset($attributes['cdk_id']) ? (int) $attributes['cdk_id'] : null;
            $scopes[($year ?? 'all') . ':' . ($cdkId ?? 'all')] = [$year, $cdkId];
        }

        if ($scopes === []) {
            return;
        }

        $publish = function () use ($domain, $scopes): void {
            foreach ($scopes as [$year, $cdkId]) {
                $this->invalidate($domain, $year, $cdkId);
                event(new PublicDashboardChanged($domain, $year, $cdkId));
            }
        };

        $connection = $model->getConnection();
        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit($publish);
        } else {
            $publish();
        }
    }

    private function invalidate(string $domain, ?int $year, ?int $cdkId): void
    {
        $currentYear = (int) date('Y');
        $years = $year === null ? range($currentYear, 2021) : [$year];
        $scopes = array_unique(['all', $cdkId === null ? 'all' : (string) $cdkId]);

        foreach ($scopes as $scope) {
            foreach ($years as $affectedYear) {
                $key = match ($domain) {
                    'pembinaan' => "pembinaan_stats_v2_{$affectedYear}_{$scope}",
                    'perlindungan' => "perlindungan_stats_v2_{$affectedYear}_{$scope}",
                    'bina_usaha' => "bina_usaha_stats_v3_{$affectedYear}_{$scope}",
                    'kelembagaan_ps' => "kelembagaan_ps_yearly_stats_v2_{$affectedYear}_{$scope}",
                    'kelembagaan_hr' => "kelembagaan_hr_yearly_stats_v2_{$affectedYear}_{$scope}",
                    'kepegawaian' => "kepegawaian_stats_{$affectedYear}_{$scope}",
                };
                Cache::forget($key);
            }

            if ($domain === 'bina_usaha' && $year === null) {
                Cache::forget("pbphh_public_static_stats_v1_{$scope}");
            }
            if ($domain === 'kelembagaan_ps' && $year === null) {
                Cache::forget("kelembagaan_ps_static_stats_v2_{$scope}");
            }
            if ($domain === 'kelembagaan_hr' && $year === null) {
                Cache::forget("kelembagaan_hr_static_stats_v2_{$scope}");
            }
            if ($domain === 'kepegawaian') {
                foreach (range($currentYear, 2021) as $visibleYear) {
                    Cache::forget("kepegawaian_stats_{$visibleYear}_{$scope}");
                }
                Cache::forget('kepegawaian_yoy_stats_' . implode('_', range($currentYear, 2021)) . "_{$scope}");
            }

            Cache::forget("public_yoy_dashboard_stats_v3_{$currentYear}_{$scope}");
        }
    }
}
