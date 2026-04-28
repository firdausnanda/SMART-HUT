<?php

namespace App\Http\Controllers;

use App\Models\HasilHutanBukanKayu;
use App\Models\HasilHutanBukanKayuDetail;
use App\Models\HasilHutanKayu;
use App\Models\Kups;
use App\Models\NilaiEkonomi;
use App\Models\Pbphh;
use App\Models\RealisasiPnbp;
use App\Models\RehabLahan;
use App\Models\PenghijauanLingkungan;
use App\Models\RehabManggrove;
use App\Models\ReboisasiPS;
use App\Models\RhlTeknis;
use App\Models\SkemaPerhutananSosial;
use App\Models\Skps;
use App\Models\SumberDana;
use App\Models\KebakaranHutan;
use App\Models\PengunjungWisata;
use App\Models\PerkembanganKth;
use App\Models\NilaiTransaksiEkonomi;
use App\Models\NilaiTransaksiEkonomiDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use App\Exports\RehabLahanExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\RekapBulananPegawai;
use App\Models\RekapStatistikBulanan;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = date('Y');
        $chartYear = $request->input('year', $currentYear);

        // Define cache key based on year
        $cacheKey = "dashboard_stats_v2_{$chartYear}";

        // Cache the heavy statistics and chart data for 10 minutes (600 seconds)
        $dashboardData = Cache::remember($cacheKey, 300, function () use ($chartYear) {
            // --- Rehab Lahan Stats (Existing) ---
            $totalRehabCurrent = RehabLahan::where('year', $chartYear)->where('status', 'final')->sum('realization');
            $totalRehabPrev = RehabLahan::where('year', $chartYear - 1)->where('status', 'final')->sum('realization');

            $rehabGrowth = 0;
            if ($totalRehabPrev > 0) {
                $rehabGrowth = (($totalRehabCurrent - $totalRehabPrev) / $totalRehabPrev) * 100;
            } elseif ($totalRehabCurrent > 0) {
                $rehabGrowth = 100;
            }

            // --- Produksi Kayu Stats (New) ---
            $kayuCurrent = HasilHutanKayu::where('year', $chartYear)
                ->where('status', 'final')
                ->sum('volume_target');

            $kayuPrev = HasilHutanKayu::where('year', $chartYear - 1)
                ->where('status', 'final')
                ->sum('volume_target');

            $kayuGrowth = 0;
            if ($kayuPrev > 0) {
                $kayuGrowth = (($kayuCurrent - $kayuPrev) / $kayuPrev) * 100;
            } elseif ($kayuCurrent > 0) {
                $kayuGrowth = 100;
            }

            // --- Transaksi Ekonomi (PNBP) Stats (New) ---
            // Optimized: Use raw SQL for cleaning and summing instead of loading all records
            $pnbpCurrent = RealisasiPnbp::where('year', $chartYear)
                ->where('status', 'final')
                ->sum("pnbp_target");

            $pnbpPrev = RealisasiPnbp::where('year', $chartYear - 1)
                ->where('status', 'final')
                ->sum("pnbp_target");

            $pnbpGrowth = 0;
            if ($pnbpPrev > 0) {
                $pnbpGrowth = (($pnbpCurrent - $pnbpPrev) / $pnbpPrev) * 100;
            } elseif ($pnbpCurrent > 0) {
                $pnbpGrowth = 100;
            }

            // --- KUPS Stats (New) ---
            $kupsTotal = Kups::where('status', 'final')->count();

            // --- NTE Stats (New) ---
            $nteCurrent = NilaiTransaksiEkonomi::where('year', $chartYear)->where('status', 'final')->sum('total_nilai_transaksi');
            $ntePrev = NilaiTransaksiEkonomi::where('year', $chartYear - 1)->where('status', 'final')->sum('total_nilai_transaksi');
            $nteGrowth = $ntePrev > 0 ? (($nteCurrent - $ntePrev) / $ntePrev) * 100 : ($nteCurrent > 0 ? 100 : 0);

            // --- Nekon Stats (New) ---
            $nekonCurrent = NilaiEkonomi::where('year', $chartYear)->where('status', 'final')->sum('total_transaction_value');
            $nekonPrev = NilaiEkonomi::where('year', $chartYear - 1)->where('status', 'final')->sum('total_transaction_value');
            $nekonGrowth = $nekonPrev > 0 ? (($nekonCurrent - $nekonPrev) / $nekonPrev) * 100 : ($nekonCurrent > 0 ? 100 : 0);

            // --- Jasa Lingkungan Stats (New) ---
            $jasaLingkunganCurrent = PengunjungWisata::where('year', $chartYear)->where('status', 'final')->sum('gross_income');
            $jasaLingkunganPrev = PengunjungWisata::where('year', $chartYear - 1)->where('status', 'final')->sum('gross_income');
            $jasaLingkunganGrowth = $jasaLingkunganPrev > 0 ? (($jasaLingkunganCurrent - $jasaLingkunganPrev) / $jasaLingkunganPrev) * 100 : ($jasaLingkunganCurrent > 0 ? 100 : 0);

            // --- Penghijauan Lingkungan Stats (New) ---
            $penghijauanCurrent = PenghijauanLingkungan::where('year', $chartYear)->where('status', 'final')->sum('realization');
            $penghijauanPrev = PenghijauanLingkungan::where('year', $chartYear - 1)->where('status', 'final')->sum('realization');
            $penghijauanGrowth = $penghijauanPrev > 0 ? (($penghijauanCurrent - $penghijauanPrev) / $penghijauanPrev) * 100 : ($penghijauanCurrent > 0 ? 100 : 0);

            // --- Produksi HHBK Stats (New) ---
            $hhbkCurrent = HasilHutanBukanKayuDetail::join('hasil_hutan_bukan_kayu', 'hasil_hutan_bukan_kayu_details.hasil_hutan_bukan_kayu_id', '=', 'hasil_hutan_bukan_kayu.id')
                ->where('hasil_hutan_bukan_kayu.year', $chartYear)->where('hasil_hutan_bukan_kayu.status', 'final')
                ->whereNull('hasil_hutan_bukan_kayu.deleted_at')->sum('hasil_hutan_bukan_kayu_details.annual_volume_realization');
            $hhbkPrev = HasilHutanBukanKayuDetail::join('hasil_hutan_bukan_kayu', 'hasil_hutan_bukan_kayu_details.hasil_hutan_bukan_kayu_id', '=', 'hasil_hutan_bukan_kayu.id')
                ->where('hasil_hutan_bukan_kayu.year', $chartYear - 1)->where('hasil_hutan_bukan_kayu.status', 'final')
                ->whereNull('hasil_hutan_bukan_kayu.deleted_at')->sum('hasil_hutan_bukan_kayu_details.annual_volume_realization');
            $hhbkGrowth = $hhbkPrev > 0 ? (($hhbkCurrent - $hhbkPrev) / $hhbkPrev) * 100 : ($hhbkCurrent > 0 ? 100 : 0);

            // --- Reboisasi PS Stats (New) ---
            $reboisasiPsCurrent = ReboisasiPS::where('year', $chartYear)->where('status', 'final')->sum('realization');
            $reboisasiPsPrev = ReboisasiPS::where('year', $chartYear - 1)->where('status', 'final')->sum('realization');
            $reboisasiPsGrowth = $reboisasiPsPrev > 0 ? (($reboisasiPsCurrent - $reboisasiPsPrev) / $reboisasiPsPrev) * 100 : ($reboisasiPsCurrent > 0 ? 100 : 0);

            // --- HKm Stats (New) ---
            $skpsTotal = Skps::where('status', 'final')->count();
            $hkmTotal = Skps::where('status', 'final')
                ->where('id_skema_perhutanan_sosial', 2)->count();
            $hkmPercentage = $skpsTotal > 0 ? ($hkmTotal / $skpsTotal) * 100 : 0;

            // --- Kebakaran Hutan Stats (New) ---
            $firesCurrent = KebakaranHutan::where('year', $chartYear)->where('status', 'final')->sum('number_of_fires');
            $firesPrev = KebakaranHutan::where('year', $chartYear - 1)->where('status', 'final')->sum('number_of_fires');
            $firesGrowth = $firesPrev > 0 ? (($firesCurrent - $firesPrev) / $firesPrev) * 100 : ($firesCurrent > 0 ? 100 : 0);

            // --- Charts Data (Rehab Lahan) ---
            $monthlyData = RehabLahan::selectRaw('month, SUM(realization) as total')
                ->where('year', $chartYear)
                ->where('status', 'final')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('total', 'month');

            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $monthlyData->get($i, 0);
            }

            // --- KUPS Chart Data ---
            $kupsByClass = Kups::select('category', DB::raw('count(*) as total'))
                ->where('status', 'final')
                ->groupBy('category')
                ->get();

            $kupsChart = [
                'labels' => $kupsByClass->pluck('category'),
                'data' => $kupsByClass->pluck('total'),
            ];

            return [
                'stats' => [
                    'rehabilitation' => [
                        'total' => $totalRehabCurrent,
                        'growth' => round($rehabGrowth, 1),
                    ],
                    'wood_production' => [
                        'total' => $kayuCurrent,
                        'growth' => round($kayuGrowth, 1),
                    ],
                    'economy' => [
                        'total' => $pnbpCurrent,
                        'growth' => round($pnbpGrowth, 1),
                    ],
                    'kups' => [
                        'total' => $kupsTotal,
                    ],
                    'nte' => [
                        'total' => $nteCurrent,
                        'growth' => round($nteGrowth, 1),
                    ],
                    'nekon' => [
                        'total' => $nekonCurrent,
                        'growth' => round($nekonGrowth, 1),
                    ],
                    'jasa_lingkungan' => [
                        'total' => $jasaLingkunganCurrent,
                        'growth' => round($jasaLingkunganGrowth, 1),
                    ],
                    'penghijauan_lingkungan' => [
                        'total' => $penghijauanCurrent,
                        'growth' => round($penghijauanGrowth, 1),
                    ],
                    'produksi_hhbk' => [
                        'total' => $hhbkCurrent,
                        'growth' => round($hhbkGrowth, 1),
                    ],
                    'reboisasi_ps' => [
                        'total' => $reboisasiPsCurrent,
                        'growth' => round($reboisasiPsGrowth, 1),
                    ],
                    'hkm' => [
                        'total' => $hkmTotal,
                        'percentage' => round($hkmPercentage, 1),
                        'skps_total' => $skpsTotal,
                    ],
                    'fires' => [
                        'total' => $firesCurrent,
                        'growth' => round($firesGrowth, 1),
                    ]
                ],
                'chartData' => $chartData,
                'kupsChart' => $kupsChart,
            ];
        });

        // --- Available Years ---
        // Generate last 5 years including current year
        $thisYear = (int) date('Y');
        $availableYears = range($thisYear, $thisYear - 4);

        // --- Recent Activity ---
        // Do not cache activities to keep them real-time
        $activities = Activity::latest()
            ->take(5)
            ->with(['causer', 'causer.roles'])
            ->get()
            ->map(function ($activity) {
                $description = $activity->description;

                switch ($activity->event) {
                    case 'created':
                        $description = 'Menambahkan data';
                        break;
                    case 'updated':
                        $description = 'Merubah data';
                        break;
                    case 'deleted':
                        $description = 'Menghapus Data';
                        break;
                }

                if ($activity->description === 'logged in') {
                    $description = 'Masuk Aplikasi';
                } elseif ($activity->description === 'logged out') {
                    $description = 'Keluar Aplikasi';
                }

                return [
                    'id' => $activity->id,
                    'description' => $description,
                    'causer' => $activity->causer ? $activity->causer->name : 'System',
                    'role' => $activity->causer && $activity->causer->roles->isNotEmpty()
                        ? $activity->causer->roles->first()->description
                        : '-',
                    'created_at' => $activity->created_at->diffForHumans(),
                    'subject_type' => class_basename($activity->subject_type),
                    'event' => $activity->event,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $dashboardData['stats'],
            'chartData' => $dashboardData['chartData'],
            'kupsChart' => $dashboardData['kupsChart'],
            'filters' => [
                'year' => (int) $chartYear
            ],
            'availableYears' => $availableYears,
            'recentActivities' => $activities
        ]);
    }

    public function publicDashboard(Request $request)
    {
        $currentYear = $request->input('year', date('Y'));

        // Generate current year and 5 years back
        $thisYear = (int) date('Y');
        $availableYears = range($thisYear, $thisYear - 5);

        return Inertia::render('Public/PublicDashboard', [
            'currentYear' => $currentYear,
            'availableYears' => $availableYears,
            'stats' => [
                'pembinaan' => $this->getPembinaanStats($currentYear),
                'perlindungan' => $this->getPerlindunganStats($currentYear),
                'bina_usaha' => $this->getBinaUsahaStats($currentYear),
                'kelembagaan_ps' => $this->getKelembagaanPsStats($currentYear),
                'kelembagaan_hr' => $this->getKelembagaanHrStats($currentYear),
                'kepegawaian' => $this->getKepegawaianStats($currentYear),
            ]
        ]);
    }

    public function publicYoYDashboard(Request $request)
    {
        $thisYear = (int) date('Y');
        $years = range($thisYear, 2021);

        $cacheKey = 'public_yoy_dashboard_stats_' . $thisYear;

        // Cache the entire YoY stats for 10 minutes (600 seconds)
        $stats = Cache::remember($cacheKey, 600, function () use ($years) {
            // Ambil data kepegawaian YoY sekaligus (1 query)
            $kepegawaianYoY = $this->getKepegawaianYoYStats($years);

            $result = [];
            foreach ($years as $year) {
                $result[$year] = [
                    'pembinaan' => $this->getPembinaanStats($year),
                    'perlindungan' => $this->getPerlindunganStats($year),
                    'bina_usaha' => $this->getBinaUsahaStats($year),
                    'kelembagaan_ps' => $this->getKelembagaanPsStats($year),
                    'kelembagaan_hr' => $this->getKelembagaanHrStats($year),
                    'kepegawaian' => $kepegawaianYoY[$year] ?? [],
                ];
            }
            return $result;
        });

        return Inertia::render('Public/PublicYoYDashboard', [
            'years' => $years,
            'stats' => $stats
        ]);
    }

    private function getPembinaanStats($currentYear)
    {
        return Cache::remember("pembinaan_stats_{$currentYear}", 300, function () use ($currentYear) {
            // Helper for standard rehab stats
            $getStats = function ($modelClass, $tableName) use ($currentYear) {
                $baseQuery = $modelClass::where('year', $currentYear)->where('status', 'final');

                return [
                    'total' => (float) (clone $baseQuery)->sum('realization'),
                    'target_total' => (float) (clone $baseQuery)->sum('target_annual'),
                    'chart' => $this->fillMonths((clone $baseQuery)->selectRaw('month, sum(realization) as total')
                        ->groupBy('month')->orderBy('month')->pluck('total', 'month')),
                    'target_chart' => $this->fillMonths((clone $baseQuery)->selectRaw('month, sum(target_annual) as total')
                        ->groupBy('month')->orderBy('month')->pluck('total', 'month')),
                    'fund' => SumberDana::leftJoin($tableName, function ($join) use ($tableName, $currentYear) {
                        $join->on('m_sumber_dana.name', '=', "$tableName.fund_source")
                            ->where("$tableName.year", '=', $currentYear)
                            ->where("$tableName.status", '=', 'final')
                            ->whereNull("$tableName.deleted_at");
                    })
                        ->selectRaw("m_sumber_dana.name as fund, count($tableName.id) as total")
                        ->groupBy('m_sumber_dana.name')->pluck('total', 'fund'),
                    'regency' => (clone $baseQuery)->join('m_regencies', "$tableName.regency_id", '=', 'm_regencies.id')
                        ->selectRaw('m_regencies.name as regency, count(*) as total')
                        ->groupBy('m_regencies.name')->pluck('total', 'regency')
                ];
            };

            // 1. Pembinaan Hutan (Rehab Lahan)
            $rehabStats = $getStats(RehabLahan::class, 'rehab_lahan');

            // 1.1 Penghijauan Lingkungan
            $penghijauanStats = $getStats(PenghijauanLingkungan::class, 'penghijauan_lingkungan');

            // 1.2 Rehabilitasi Mangrove
            $manggroveStats = $getStats(RehabManggrove::class, 'rehab_manggrove');

            // 1.3 Reboisasi PS
            $reboisasiStats = $getStats(ReboisasiPS::class, 'reboisasi_ps');

            // Add Pengelola Stats for Reboisasi PS
            $reboisasiStats['pengelola'] = ReboisasiPS::join('m_pengelola_ps', 'reboisasi_ps.pengelola_id', '=', 'm_pengelola_ps.id')
                ->where('reboisasi_ps.year', $currentYear)
                ->where('reboisasi_ps.status', 'final')
                ->selectRaw('m_pengelola_ps.name as pengelola, count(reboisasi_ps.id) as total')
                ->groupBy('m_pengelola_ps.name')
                ->pluck('total', 'pengelola');

            // 1.4 RHL Teknis (Optimized with SQL joins)
            $rhlBase = RhlTeknis::where('year', $currentYear)->where('status', 'final');

            $rhlTeknisTotal = RhlTeknis::join('rhl_teknis_details', 'rhl_teknis.id', '=', 'rhl_teknis_details.rhl_teknis_id')
                ->where('rhl_teknis.year', $currentYear)
                ->where('rhl_teknis.status', 'final')
                ->sum('rhl_teknis_details.unit_amount');

            $rhlTeknisTargetTotal = (clone $rhlBase)->sum('target_annual');

            $rhlTeknisChart = $this->fillMonths(RhlTeknis::join('rhl_teknis_details', 'rhl_teknis.id', '=', 'rhl_teknis_details.rhl_teknis_id')
                ->where('rhl_teknis.year', $currentYear)
                ->where('rhl_teknis.status', 'final')
                ->selectRaw('month, sum(rhl_teknis_details.unit_amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'));

            $rhlTeknisTargetChart = $this->fillMonths((clone $rhlBase)
                ->selectRaw('month, sum(target_annual) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'));

            $rhlTeknisFund = SumberDana::leftJoin('rhl_teknis', function ($join) use ($currentYear) {
                $join->on('m_sumber_dana.name', '=', 'rhl_teknis.fund_source')
                    ->where('rhl_teknis.year', '=', $currentYear)
                    ->where('rhl_teknis.status', '=', 'final')
                    ->whereNull('rhl_teknis.deleted_at');
            })
                ->selectRaw('m_sumber_dana.name as fund, count(rhl_teknis.id) as total')
                ->groupBy('m_sumber_dana.name')
                ->pluck('total', 'fund');

            $rhlTeknisType = \App\Models\RhlTeknisDetail::join('rhl_teknis', 'rhl_teknis_details.rhl_teknis_id', '=', 'rhl_teknis.id')
                ->join('m_bangunan_kta', 'rhl_teknis_details.bangunan_kta_id', '=', 'm_bangunan_kta.id')
                ->where('rhl_teknis.year', $currentYear)
                ->where('rhl_teknis.status', 'final')
                ->whereNull('rhl_teknis.deleted_at')
                ->selectRaw('m_bangunan_kta.name as type, sum(rhl_teknis_details.unit_amount) as total')
                ->groupBy('m_bangunan_kta.name')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'type');

            return [
                'rehab_total' => $rehabStats['total'],
                'rehab_target_total' => $rehabStats['target_total'],
                'rehab_chart' => $rehabStats['chart'],
                'rehab_target_chart' => $rehabStats['target_chart'],
                'rehab_fund' => $rehabStats['fund'],
                'rehab_regency' => $rehabStats['regency'],

                'penghijauan_total' => $penghijauanStats['total'],
                'penghijauan_target_total' => $penghijauanStats['target_total'],
                'penghijauan_chart' => $penghijauanStats['chart'],
                'penghijauan_target_chart' => $penghijauanStats['target_chart'],
                'penghijauan_fund' => $penghijauanStats['fund'],
                'penghijauan_regency' => $penghijauanStats['regency'],

                'manggrove_total' => $manggroveStats['total'],
                'manggrove_target_total' => $manggroveStats['target_total'],
                'manggrove_chart' => $manggroveStats['chart'],
                'manggrove_target_chart' => $manggroveStats['target_chart'],
                'manggrove_fund' => $manggroveStats['fund'],
                'manggrove_regency' => $manggroveStats['regency'],

                'reboisasi_total' => $reboisasiStats['total'],
                'reboisasi_target_total' => $reboisasiStats['target_total'],
                'reboisasi_chart' => $reboisasiStats['chart'],
                'reboisasi_target_chart' => $reboisasiStats['target_chart'],
                'reboisasi_fund' => $reboisasiStats['fund'],
                'reboisasi_regency' => $reboisasiStats['regency'],
                'reboisasi_pengelola' => $reboisasiStats['pengelola'],

                'rhl_teknis_total' => (float) $rhlTeknisTotal,
                'rhl_teknis_target_total' => (float) $rhlTeknisTargetTotal,
                'rhl_teknis_chart' => $rhlTeknisChart,
                'rhl_teknis_target_chart' => $rhlTeknisTargetChart,
                'rhl_teknis_fund' => $rhlTeknisFund,
                'rhl_teknis_type' => $rhlTeknisType,
            ];
        });
    }

    private function getPerlindunganStats($currentYear)
    {
        return Cache::remember("perlindungan_stats_{$currentYear}", 300, function () use ($currentYear) {
            // --- 2. Perlindungan Hutan ---
            // Kebakaran
            $kebakaranStats = KebakaranHutan::where('year', $currentYear)
                ->where('status', 'final')
                ->selectRaw('SUM(number_of_fires) as total_kejadian, SUM(fire_area) as total_area')
                ->first();

            $kebakaranMonthlyRaw = KebakaranHutan::where('year', $currentYear)
                ->where('status', 'final')
                ->selectRaw('month, sum(number_of_fires) as incidents, sum(fire_area) as area')
                ->groupBy('month')
                ->get();

            $kebakaranChart = $this->fillMonths($kebakaranMonthlyRaw->pluck('incidents', 'month'));
            $kebakaranMonthlyData = [];
            for ($i = 1; $i <= 12; $i++) {
                $item = $kebakaranMonthlyRaw->where('month', $i)->first();
                $kebakaranMonthlyData[$i] = [
                    'incidents' => (int) ($item->incidents ?? 0),
                    'area' => (float) ($item->area ?? 0),
                ];
            }

            $kebakaranByPengelola = KebakaranHutan::where('kebakaran_hutan.year', $currentYear)
                ->where('kebakaran_hutan.status', 'final')
                ->join('m_pengelola_wisata', 'kebakaran_hutan.id_pengelola_wisata', '=', 'm_pengelola_wisata.id')
                ->selectRaw('m_pengelola_wisata.name as pengelola, sum(number_of_fires) as incidents, sum(fire_area) as area')
                ->groupBy('m_pengelola_wisata.name')
                ->get()
                ->keyBy('pengelola');

            // Wisata
            $wisataStats = PengunjungWisata::where('year', $currentYear)
                ->where('status', 'final')
                ->selectRaw('SUM(number_of_visitors) as total_visitors, SUM(gross_income) as total_income')
                ->first();

            $wisataMonthlyRaw = PengunjungWisata::where('year', $currentYear)
                ->where('status', 'final')
                ->selectRaw('month, sum(number_of_visitors) as visitors, sum(gross_income) as income')
                ->groupBy('month')
                ->get();

            $wisataMonthlyStats = [];
            for ($i = 1; $i <= 12; $i++) {
                $item = $wisataMonthlyRaw->where('month', $i)->first();
                $wisataMonthlyStats[$i] = [
                    'visitors' => (int) ($item->visitors ?? 0),
                    'income' => (float) ($item->income ?? 0),
                ];
            }

            $wisataByPengelola = PengunjungWisata::where('pengunjung_wisata.year', $currentYear)
                ->where('pengunjung_wisata.status', 'final')
                ->join('m_pengelola_wisata', 'pengunjung_wisata.id_pengelola_wisata', '=', 'm_pengelola_wisata.id')
                ->selectRaw('m_pengelola_wisata.name as pengelola, sum(number_of_visitors) as visitors, sum(gross_income) as income')
                ->groupBy('m_pengelola_wisata.name')
                ->get()
                ->keyBy('pengelola');

            return [
                'kebakaran_kejadian' => (int) ($kebakaranStats->total_kejadian ?? 0),
                'kebakaran_area' => (float) ($kebakaranStats->total_area ?? 0),
                'kebakaranChart' => $kebakaranChart,
                'kebakaranMonthly' => $kebakaranMonthlyData,
                'kebakaranByPengelola' => $kebakaranByPengelola,
                'wisata_visitors' => (int) ($wisataStats->total_visitors ?? 0),
                'wisata_income' => (float) ($wisataStats->total_income ?? 0),
                'wisataMonthly' => $wisataMonthlyStats,
                'wisataByPengelola' => $wisataByPengelola,
            ];
        });
    }

    private function getBinaUsahaStats($currentYear)
    {
        return Cache::remember("bina_usaha_stats_{$currentYear}", 300, function () use ($currentYear) {
            // --- 3. Bina Usaha (Split into 5 categories) ---
            $forestTypes = ['Hutan Negara', 'Perhutanan Sosial', 'Hutan Rakyat'];
            $binaUsahaData = [];

            // Optimization: Pre-fetch data for all types to minimize queries inside loop
            $kayuTotals = HasilHutanKayu::where('year', $currentYear)
                ->where('status', 'final')
                ->groupBy('forest_type')
                ->pluck(DB::raw('sum(volume_target)'), 'forest_type');

            $kayuMonthlyByForestType = HasilHutanKayu::where('year', $currentYear)
                ->where('status', 'final')
                ->selectRaw('forest_type, month, sum(volume_target) as total')
                ->groupBy('forest_type', 'month')
                ->get()
                ->groupBy('forest_type');

            $bukanKayuTotals = HasilHutanBukanKayu::where('year', $currentYear)
                ->where('status', 'final')
                ->groupBy('forest_type')
                ->pluck(DB::raw('sum(volume_target)'), 'forest_type');

            $bukanKayuMonthlyByForestType = HasilHutanBukanKayu::where('year', $currentYear)
                ->where('status', 'final')
                ->selectRaw('forest_type, month, sum(volume_target) as total')
                ->groupBy('forest_type', 'month')
                ->get()
                ->groupBy('forest_type');

            foreach ($forestTypes as $type) {
                $key = strtolower(str_replace(' ', '_', $type));

                // Kayu Realization (Sum from details)
                $kayuRealization = HasilHutanKayu::join('hasil_hutan_kayu_details', 'hasil_hutan_kayu.id', '=', 'hasil_hutan_kayu_details.hasil_hutan_kayu_id')
                    ->where('hasil_hutan_kayu.year', $currentYear)
                    ->where('hasil_hutan_kayu.status', 'final')
                    ->where('hasil_hutan_kayu.forest_type', $type)
                    ->sum('hasil_hutan_kayu_details.volume_realization');

                // Kayu Monthly Realization
                $kayuMonthlyRealization = HasilHutanKayu::join('hasil_hutan_kayu_details', 'hasil_hutan_kayu.id', '=', 'hasil_hutan_kayu_details.hasil_hutan_kayu_id')
                    ->where('hasil_hutan_kayu.year', $currentYear)
                    ->where('hasil_hutan_kayu.status', 'final')
                    ->where('hasil_hutan_kayu.forest_type', $type)
                    ->selectRaw('month, sum(hasil_hutan_kayu_details.volume_realization) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month');

                $binaUsahaData[$key]['kayu_total'] = (float) $kayuRealization;
                $binaUsahaData[$key]['kayu_target'] = (float) ($kayuTotals[$type] ?? 0);
                $binaUsahaData[$key]['kayu_monthly'] = $this->fillMonths($kayuMonthlyRealization);
                $binaUsahaData[$key]['kayu_target_monthly'] = $this->fillMonths(
                    isset($kayuMonthlyByForestType[$type])
                    ? $kayuMonthlyByForestType[$type]->pluck('total', 'month')
                    : []
                );

                $binaUsahaData[$key]['kayu_commodity'] = HasilHutanKayu::join('hasil_hutan_kayu_details', 'hasil_hutan_kayu.id', '=', 'hasil_hutan_kayu_details.hasil_hutan_kayu_id')
                    ->join('m_kayu', 'hasil_hutan_kayu_details.kayu_id', '=', 'm_kayu.id')
                    ->where('hasil_hutan_kayu.year', $currentYear)
                    ->where('hasil_hutan_kayu.status', 'final')
                    ->where('hasil_hutan_kayu.forest_type', $type)
                    ->selectRaw('m_kayu.name as commodity, sum(hasil_hutan_kayu_details.volume_realization) as total')
                    ->groupBy('m_kayu.name')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->pluck('total', 'commodity');

                // Bukan Kayu Target (Excluding Bambu)
                $bukanKayuTarget = HasilHutanBukanKayu::where('year', $currentYear)
                    ->where('status', 'final')
                    ->where('forest_type', $type)
                    ->whereHas('details.bukanKayu', fn($q) => $q->where('name', '!=', 'Bambu'))
                    ->sum('volume_target');

                // Bukan Kayu Realization
                $bukanKayuRealization = (float) HasilHutanBukanKayuDetail::join('hasil_hutan_bukan_kayu', 'hasil_hutan_bukan_kayu_details.hasil_hutan_bukan_kayu_id', '=', 'hasil_hutan_bukan_kayu.id')
                    ->join('m_bukan_kayu', 'hasil_hutan_bukan_kayu_details.bukan_kayu_id', '=', 'm_bukan_kayu.id')
                    ->where('hasil_hutan_bukan_kayu.year', $currentYear)
                    ->where('hasil_hutan_bukan_kayu.status', 'final')
                    ->where('hasil_hutan_bukan_kayu.forest_type', $type)
                    ->whereNull('hasil_hutan_bukan_kayu.deleted_at')
                    ->where('m_bukan_kayu.name', '!=', 'Bambu')
                    ->sum('hasil_hutan_bukan_kayu_details.annual_volume_realization');

                $binaUsahaData[$key]['bukan_kayu_total'] = $bukanKayuRealization;
                $binaUsahaData[$key]['bukan_kayu_target'] = (float) $bukanKayuTarget;

                // Bambu Target
                $bambuTarget = HasilHutanBukanKayu::where('year', $currentYear)
                    ->where('status', 'final')
                    ->where('forest_type', $type)
                    ->whereHas('details.bukanKayu', fn($q) => $q->where('name', 'Bambu'))
                    ->sum('volume_target');

                // Bambu Realization
                $bambuRealization = (float) HasilHutanBukanKayuDetail::join('hasil_hutan_bukan_kayu', 'hasil_hutan_bukan_kayu_details.hasil_hutan_bukan_kayu_id', '=', 'hasil_hutan_bukan_kayu.id')
                    ->join('m_bukan_kayu', 'hasil_hutan_bukan_kayu_details.bukan_kayu_id', '=', 'm_bukan_kayu.id')
                    ->where('hasil_hutan_bukan_kayu.year', $currentYear)
                    ->where('hasil_hutan_bukan_kayu.status', 'final')
                    ->where('hasil_hutan_bukan_kayu.forest_type', $type)
                    ->whereNull('hasil_hutan_bukan_kayu.deleted_at')
                    ->where('m_bukan_kayu.name', 'Bambu')
                    ->sum('hasil_hutan_bukan_kayu_details.annual_volume_realization');

                $binaUsahaData[$key]['bambu_total'] = $bambuRealization;
                $binaUsahaData[$key]['bambu_target'] = (float) $bambuTarget;

                $binaUsahaData[$key]['bukan_kayu_monthly'] = $this->fillMonths(
                    isset($bukanKayuMonthlyByForestType[$type])
                    ? $bukanKayuMonthlyByForestType[$type]->pluck('total', 'month')
                    : []
                );

                $binaUsahaData[$key]['bukan_kayu_commodity'] = HasilHutanBukanKayu::join('hasil_hutan_bukan_kayu_details', 'hasil_hutan_bukan_kayu.id', '=', 'hasil_hutan_bukan_kayu_details.hasil_hutan_bukan_kayu_id')
                    ->join('m_bukan_kayu', 'hasil_hutan_bukan_kayu_details.bukan_kayu_id', '=', 'm_bukan_kayu.id')
                    ->where('hasil_hutan_bukan_kayu.year', $currentYear)
                    ->where('hasil_hutan_bukan_kayu.status', 'final')
                    ->where('hasil_hutan_bukan_kayu.forest_type', $type)
                    ->selectRaw('m_bukan_kayu.name as commodity, sum(hasil_hutan_bukan_kayu_details.annual_volume_realization) as total')
                    ->groupBy('m_bukan_kayu.name')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->pluck('total', 'commodity');
            }

            // PBPHH
            $pbphhStats = [
                'total_units' => Pbphh::where('status', 'final')->count(),
                'total_workers' => Pbphh::where('status', 'final')->sum('number_of_workers'),
                'total_investment' => Pbphh::where('status', 'final')->sum('investment_value'),
                'by_regency' => Pbphh::join('m_regencies', 'pbphh.regency_id', '=', 'm_regencies.id')
                    ->where('pbphh.status', 'final')
                    ->selectRaw('m_regencies.name as regency, count(*) as count')
                    ->groupBy('m_regencies.name')
                    ->pluck('count', 'regency'),
                'by_production_type' => Pbphh::join('pbphh_jenis_produksi', 'pbphh.id', '=', 'pbphh_jenis_produksi.pbphh_id')
                    ->join('m_jenis_produksi', 'pbphh_jenis_produksi.jenis_produksi_id', '=', 'm_jenis_produksi.id')
                    ->where('pbphh.status', 'final')
                    ->selectRaw('m_jenis_produksi.name as type, count(distinct pbphh.id) as count')
                    ->groupBy('m_jenis_produksi.name')
                    ->get()
                    ->toArray(),
                'by_condition' => Pbphh::where('status', 'final')
                    ->selectRaw('present_condition as condition_name, count(*) as count')
                    ->groupBy('present_condition')
                    ->get()
                    ->toArray()
            ];

            // PNBP - Improved: Use DECIMAL for accurate summing of potential decimal values
            $pnbpRealizationSql = "CAST(pnbp_realization AS DECIMAL(15,2))";

            $pnbpStats = [
                'total_realization' => (float) RealisasiPnbp::where('year', $currentYear)->where('status', 'final')->sum(DB::raw($pnbpRealizationSql)),
                'total_target' => (float) RealisasiPnbp::where('year', $currentYear)->where('status', 'final')->sum('pnbp_target'),
                'monthly' => (function () use ($currentYear, $pnbpRealizationSql) {
                    $raw = RealisasiPnbp::where('year', $currentYear)
                        ->where('status', 'final')
                        ->selectRaw("month, sum($pnbpRealizationSql) as realization, sum(pnbp_target) as target")
                        ->groupBy('month')
                        ->get();
                    $filled = [];
                    for ($i = 1; $i <= 12; $i++) {
                        $item = $raw->where('month', $i)->first();
                        $filled[$i] = [
                            'realization' => (float) ($item->realization ?? 0),
                            'target' => (float) ($item->target ?? 0),
                        ];
                    }
                    return $filled;
                })(),
                'by_regency' => RealisasiPnbp::join('m_regencies', 'realisasi_pnbp.regency_id', '=', 'm_regencies.id')
                    ->where('realisasi_pnbp.year', $currentYear)
                    ->where('realisasi_pnbp.status', 'final')
                    ->selectRaw("m_regencies.name as regency, sum($pnbpRealizationSql) as total")
                    ->groupBy('m_regencies.name')
                    ->pluck('total', 'regency'),
                'by_pengelola' => RealisasiPnbp::join('m_pengelola_wisata', 'realisasi_pnbp.id_pengelola_wisata', '=', 'm_pengelola_wisata.id')
                    ->where('realisasi_pnbp.year', $currentYear)
                    ->where('realisasi_pnbp.status', 'final')
                    ->selectRaw("m_pengelola_wisata.name as pengelola, SUM($pnbpRealizationSql) as total")
                    ->groupBy('m_pengelola_wisata.name')
                    ->pluck('total', 'pengelola')->toArray()
            ];

            return [
                'hutan_negara' => $binaUsahaData['hutan_negara'],
                'perhutanan_sosial' => $binaUsahaData['perhutanan_sosial'],
                'hutan_rakyat' => $binaUsahaData['hutan_rakyat'],
                'pbphh' => $pbphhStats,
                'pnbp' => $pnbpStats,
            ];
        });
    }

    private function getKelembagaanPsStats($currentYear)
    {
        // Cache static data (not dependent on year) for 10 minutes (600 seconds)
        $staticStats = Cache::remember('kelembagaan_ps_static_stats', 600, function () {
            return [
                'kelompok_count' => Skps::where('status', 'final')->count(),
                'area_total' => (float) Skps::where('status', 'final')->sum('ps_area'),
                'kk_total' => (int) Skps::where('status', 'final')->sum('number_of_kk'),
            ];
        });

        // Cache yearly data for 10 minutes (600 seconds)
        $yearlyStats = Cache::remember("kelembagaan_ps_yearly_stats_{$currentYear}", 600, function () use ($currentYear) {
            return [
                'nekon_total' => (float) NilaiEkonomi::where('year', $currentYear)->where('status', 'final')->sum('total_transaction_value'),
                'scheme_distribution' => SkemaPerhutananSosial::leftJoin('skps', function ($join) {
                    $join->on('m_skema_perhutanan_sosial.id', '=', 'skps.id_skema_perhutanan_sosial')
                        ->where('skps.status', 'final');
                })
                    ->selectRaw('m_skema_perhutanan_sosial.name as scheme, count(skps.id) as count')
                    ->groupBy('m_skema_perhutanan_sosial.id', 'm_skema_perhutanan_sosial.name')
                    ->get(),
                'economic_by_regency' => NilaiEkonomi::join('m_regencies', 'nilai_ekonomi.regency_id', '=', 'm_regencies.id')
                    ->where('nilai_ekonomi.year', $currentYear)
                    ->where('nilai_ekonomi.status', 'final')
                    ->selectRaw('m_regencies.name as regency, sum(total_transaction_value) as total')
                    ->groupBy('m_regencies.name')
                    ->pluck('total', 'regency'),
                'top_groups' => NilaiEkonomi::where('year', $currentYear)
                    ->where('status', 'final')
                    ->selectRaw('nama_kelompok as group_name, sum(total_transaction_value) as total')
                    ->groupBy('nama_kelompok')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->pluck('total', 'group_name')
            ];
        });

        return array_merge($staticStats, $yearlyStats);
    }
    
    private function getKepegawaianStats($currentYear)
    {
        return Cache::remember("kepegawaian_stats_{$currentYear}", 300, function () use ($currentYear) {
            // 1. Coba ambil dari tabel rekap_statistik_bulanan (Data Snapshot Bulanan)
            // Ambil data terbaru untuk tahun yang dipilih berdasarkan bulan terakhir yang berstatus FINAL
            $rekap = RekapStatistikBulanan::where('periode_tahun', $currentYear)
                ->where('status', 'final')
                ->orderByDesc('periode_bulan')
                ->first();

            if ($rekap) {
                // Mapping Gender dari integer columns ke format label-count
                $genderStats = [
                    'Laki-laki' => $rekap->total_laki,
                    'Perempuan' => $rekap->total_perempuan,
                ];

                $statusStats = $rekap->statistik_status_pegawai ?? [];
                $generations = $rekap->statistik_generasi ?? [];
                $pendidikanStats = $rekap->statistik_pendidikan ?? [];
                $golonganStats = $rekap->statistik_golongan ?? [];
                $pernikahanStats = $rekap->statistik_status_pernikahan ?? [];
                $totalPegawai = $rekap->total_pegawai_aktif;
                $rawBezetting = $rekap->statistik_bezetting ?? [];
                if (!empty($rawBezetting) && !isset($rawBezetting['details'])) {
                    $details = [];
                    $totalKebutuhan = 0;
                    $totalEksisting = 0;

                    foreach ($rawBezetting as $name => $values) {
                        $kebutuhan = $values['kebutuhan'] ?? 0;
                        $eksisting = $values['terisi'] ?? ($values['eksisting'] ?? 0);
                        
                        $details[] = [
                            'name' => $name,
                            'kebutuhan' => (int)$kebutuhan,
                            'eksisting' => (int)$eksisting,
                        ];
                        
                        $totalKebutuhan += $kebutuhan;
                        $totalEksisting += $eksisting;
                    }

                    $bezettingStats = [
                        'total_kebutuhan' => $totalKebutuhan,
                        'total_eksisting' => $totalEksisting,
                        'details' => $details,
                    ];
                } else {
                    $bezettingStats = $rawBezetting ?: [
                        'total_kebutuhan' => 0,
                        'total_eksisting' => 0,
                        'details' => []
                    ];
                }

                // Data Tren untuk chart tren12Bulan
                $trenBulanan = RekapStatistikBulanan::tren12Bulan()->map(fn($r) => [
                    'label' => \Carbon\Carbon::createFromDate($r->periode_tahun, $r->periode_bulan, 1)
                        ->translatedFormat('M Y'),
                    'total' => $r->total_pegawai_aktif,
                ]);

                // Sort working_years for consistency
                $workingYearsStats = [
                    '0-5 Tahun' => $rekap->statistik_masa_kerja['0-5 Tahun'] ?? 0,
                    '6-10 Tahun' => $rekap->statistik_masa_kerja['6-10 Tahun'] ?? 0,
                    '11-15 Tahun' => $rekap->statistik_masa_kerja['11-15 Tahun'] ?? 0,
                    '16-20 Tahun' => $rekap->statistik_masa_kerja['16-20 Tahun'] ?? 0,
                    '> 20 Tahun' => $rekap->statistik_masa_kerja['> 20 Tahun'] ?? 0,
                ];

                return [
                    'total_pegawai' => $totalPegawai,
                    'gender' => $genderStats,
                    'status' => $statusStats,
                    'generations' => $generations,
                    'education' => $pendidikanStats,
                    'rank' => $golonganStats,
                    'marriage' => $pernikahanStats,
                    'bezetting' => $bezettingStats,
                    // Data Baru
                    'total_pns' => $rekap->total_pns,
                    'total_pppk' => $rekap->total_pppk,
                    'total_honorer' => $rekap->total_honorer,
                    'pensiun_tahun_ini' => $rekap->total_pensiun_tahun_ini,
                    'pensiun_bulan_ini' => $rekap->total_pensiun_bulan_ini,
                    'pensiun_6_bulan' => $rekap->total_pensiun_6_bulan,
                    'kgb_bulan_ini' => $rekap->kgb_jatuh_bulan_ini,
                    'kgb_3_bulan' => $rekap->kgb_jatuh_3_bulan,
                    'periode_label' => $rekap->periode_bulan . '/' . $rekap->periode_tahun,
                    'tren_bulanan' => $trenBulanan,
                    'working_years' => $workingYearsStats,
                ];
            } elseif ($currentYear == (int) date('Y')) {
                return $this->getKepegawaianFallbackForYear($currentYear);
            }

            return [
                'total_pegawai' => 0,
                'gender' => [],
                'status' => [],
                'generations' => [],
                'education' => [],
                'rank' => [],
                'marriage' => [],
                'bezetting' => [
                    'total_kebutuhan' => 0,
                    'total_eksisting' => 0,
                    'details' => []
                ],
            ];
        });
    }

    private function getKepegawaianYoYStats(array $years): array
    {
        return Cache::remember('kepegawaian_yoy_stats_' . implode('_', $years), 300, function () use ($years) {
            // 1. Ambil semua rekap dari RekapStatistikBulanan untuk semua tahun sekaligus
            //    Ambil record terbaru (bulan terbesar) per tahun yang berstatus 'final'
            $rekaps = RekapStatistikBulanan::whereIn('periode_tahun', $years)
                ->where('status', 'final')
                ->orderByDesc('periode_bulan')
                ->get()
                ->groupBy('periode_tahun')
                ->map(fn($group) => $group->first());

            $result = [];
            foreach ($years as $year) {
                $rekap = $rekaps->get($year);

                if (!$rekap) {
                    $result[$year] = $this->getKepegawaianFallbackForYear($year);
                    continue;
                }

                // Normalisasi bezetting
                $rawBezetting = $rekap->statistik_bezetting ?? [];
                if (!empty($rawBezetting) && !isset($rawBezetting['details'])) {
                    $details = [];
                    $totalKebutuhan = 0;
                    $totalEksisting = 0;
                    foreach ($rawBezetting as $name => $values) {
                        $kebutuhan = $values['kebutuhan'] ?? 0;
                        $eksisting = $values['terisi'] ?? ($values['eksisting'] ?? 0);
                        $details[] = ['name' => $name, 'kebutuhan' => (int) $kebutuhan, 'eksisting' => (int) $eksisting];
                        $totalKebutuhan += $kebutuhan;
                        $totalEksisting += $eksisting;
                    }
                    $bezettingStats = ['total_kebutuhan' => $totalKebutuhan, 'total_eksisting' => $totalEksisting, 'details' => $details];
                } else {
                    $bezettingStats = $rawBezetting ?: ['total_kebutuhan' => 0, 'total_eksisting' => 0, 'details' => []];
                }

                $result[$year] = [
                    'total_pegawai' => $rekap->total_pegawai_aktif,
                    'total_pns' => $rekap->total_pns,
                    'total_pppk' => $rekap->total_pppk,
                    'total_honorer' => $rekap->total_honorer,
                    'gender' => ['Laki-laki' => $rekap->total_laki, 'Perempuan' => $rekap->total_perempuan],
                    'status' => $rekap->statistik_status_pegawai ?? [],
                    'generations' => $rekap->statistik_generasi ?? [],
                    'education' => $rekap->statistik_pendidikan ?? [],
                    'rank' => $rekap->statistik_golongan ?? [],
                    'marriage' => $rekap->statistik_status_pernikahan ?? [],
                    'working_years' => [
                        '0-5 Tahun' => $rekap->statistik_masa_kerja['0-5 Tahun'] ?? 0,
                        '6-10 Tahun' => $rekap->statistik_masa_kerja['6-10 Tahun'] ?? 0,
                        '11-15 Tahun' => $rekap->statistik_masa_kerja['11-15 Tahun'] ?? 0,
                        '16-20 Tahun' => $rekap->statistik_masa_kerja['16-20 Tahun'] ?? 0,
                        '> 20 Tahun' => $rekap->statistik_masa_kerja['> 20 Tahun'] ?? 0,
                    ],
                    'bezetting' => $bezettingStats,
                    'pensiun_tahun_ini' => $rekap->total_pensiun_tahun_ini,
                    'pensiun_bulan_ini' => $rekap->total_pensiun_bulan_ini,
                    'pensiun_6_bulan' => $rekap->total_pensiun_6_bulan,
                    'kgb_bulan_ini' => $rekap->kgb_jatuh_bulan_ini,
                    'kgb_3_bulan' => $rekap->kgb_jatuh_3_bulan,
                    'periode_label' => $rekap->periode_bulan . '/' . $rekap->periode_tahun,
                ];
            }

            return $result;
        });
    }

    private function getKepegawaianFallbackForYear(int $year): array
    {
        // Fallback: Ambil dari RekapBulananPegawai (Snapshot per Row) jika statistik bulanan belum di-generate
        $latestBulan = RekapBulananPegawai::where('periode_tahun', $year)
            ->where('status', 'final')
            ->orderByDesc('periode_bulan')
            ->value('periode_bulan');

        if (!$latestBulan) {
            return [
                'total_pegawai' => 0,
                'gender' => [],
                'status' => [],
                'generations' => [],
                'education' => [],
                'rank' => [],
                'marriage' => [],
                'bezetting' => [
                    'total_kebutuhan' => 0,
                    'total_eksisting' => 0,
                    'details' => []
                ],
                'total_pns' => 0,
                'total_pppk' => 0,
                'total_honorer' => 0,
                'working_years' => [],
            ];
        }

        $base = RekapBulananPegawai::where('periode_tahun', $year)
            ->where('periode_bulan', $latestBulan)
            ->where('status', 'final')
            ->where('status_kedudukan', 'Aktif');

        // 1. Total pegawai
        $totalPegawai = (clone $base)->count();

        // 2. Gender
        $genderRaw = (clone $base)->selectRaw('jenis_kelamin, count(*) as count')
            ->groupBy('jenis_kelamin')->pluck('count', 'jenis_kelamin');
        $genderStats = [
            'Laki-laki' => $genderRaw->get('Laki-laki', 0),
            'Perempuan' => $genderRaw->get('Perempuan', 0),
        ];

        // 3. Status Pegawai
        $statusStats = (clone $base)->selectRaw('status_pegawai, count(*) as count')
            ->groupBy('status_pegawai')->pluck('count', 'status_pegawai');
        $totalPns = $statusStats->get('PNS', 0);
        $totalPppk = $statusStats->get('PPPK', 0);
        $totalHonorer = $totalPegawai - $totalPns - $totalPppk;

        // 4. Generasi
        $generations = (clone $base)->selectRaw('generasi, count(*) as count')
            ->groupBy('generasi')->pluck('count', 'generasi')->toArray();

        // 5. Bezetting
        $bezettingRaw = (clone $base)
            ->join('bezettings', 'rekap_bulanan_pegawai.bezetting_id', '=', 'bezettings.id')
            ->selectRaw('bezettings.nama_jabatan, bezettings.kebutuhan, count(rekap_bulanan_pegawai.id) as eksisting')
            ->groupBy('bezettings.id', 'bezettings.nama_jabatan', 'bezettings.kebutuhan')
            ->get();
        $bezettingStats = [
            'total_kebutuhan' => $bezettingRaw->sum('kebutuhan'),
            'total_eksisting' => $bezettingRaw->sum('eksisting'),
            'details' => $bezettingRaw->map(fn($r) => [
                'name' => $r->nama_jabatan,
                'kebutuhan' => $r->kebutuhan,
                'eksisting' => $r->eksisting,
            ])->values()->toArray(),
        ];

        // 6. Masa Kerja — ambil dari RekapStatistikBulanan (terbaru) jika ada
        $rekapForMasaKerja = RekapStatistikBulanan::where('periode_tahun', $year)
            ->orderByDesc('periode_bulan')
            ->value('statistik_masa_kerja');
        $workingYearsStats = !empty($rekapForMasaKerja) ? [
            '0-5 Tahun' => $rekapForMasaKerja['0-5 Tahun'] ?? 0,
            '6-10 Tahun' => $rekapForMasaKerja['6-10 Tahun'] ?? 0,
            '11-15 Tahun' => $rekapForMasaKerja['11-15 Tahun'] ?? 0,
            '16-20 Tahun' => $rekapForMasaKerja['16-20 Tahun'] ?? 0,
            '> 20 Tahun' => $rekapForMasaKerja['> 20 Tahun'] ?? 0,
        ] : [];

        // 7. Pensiun & KGB
        $pensiunStats = (clone $base)->selectRaw("
                    SUM(CASE WHEN YEAR(proyeksi_pensiun) = ? THEN 1 ELSE 0 END) AS tahun_ini,
                    SUM(CASE WHEN proyeksi_pensiun BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS bulan_ini,
                    SUM(CASE WHEN proyeksi_pensiun BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 180 DAY) THEN 1 ELSE 0 END) AS enam_bulan
                ", [$year])->first();

        $kgbStats = (clone $base)->selectRaw("
                    SUM(CASE WHEN tmt_kgb_berikutnya BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS bulan_ini,
                    SUM(CASE WHEN tmt_kgb_berikutnya BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS tiga_bulan
                ")->first();

        return [
            'total_pegawai' => $totalPegawai,
            'gender' => $genderStats,
            'status' => $statusStats,
            'generations' => $generations,
            'education' => [],
            'rank' => [],
            'marriage' => [],
            'bezetting' => $bezettingStats,
            'total_pns' => $totalPns,
            'total_pppk' => $totalPppk,
            'total_honorer' => $totalHonorer,
            'pensiun_tahun_ini' => (int) $pensiunStats->tahun_ini,
            'pensiun_bulan_ini' => (int) $pensiunStats->bulan_ini,
            'pensiun_6_bulan' => (int) $pensiunStats->enam_bulan,
            'kgb_bulan_ini' => (int) $kgbStats->bulan_ini,
            'kgb_3_bulan' => (int) $kgbStats->tiga_bulan,
            'working_years' => $workingYearsStats,
            'periode_label' => $latestBulan . '/' . $year,
            'tren_bulanan' => [],
        ];
    }

    private function getKelembagaanHrStats($currentYear)
    {
        // Cache static data (not dependent on year) for 10 minutes (600 seconds)
        $staticStats = Cache::remember('kelembagaan_hr_static_stats', 600, function () {
            return [
                'kelompok_count' => PerkembanganKth::where('status', 'final')->count(),
                'area_total' => (float) PerkembanganKth::where('status', 'final')->sum('luas_kelola'),
                'anggota_total' => (int) PerkembanganKth::where('status', 'final')->sum('jumlah_anggota'),
            ];
        });

        // Cache yearly data for 10 minutes (600 seconds)
        $yearlyStats = Cache::remember("kelembagaan_hr_yearly_stats_{$currentYear}", 600, function () use ($currentYear) {
            return [
                'nte_total' => (float) NilaiTransaksiEkonomi::where('year', $currentYear)->where('status', 'final')->sum('total_nilai_transaksi'),
                'class_distribution' => PerkembanganKth::where('status', 'final')
                    ->selectRaw('kelas_kelembagaan as class_name, count(*) as count')
                    ->groupBy('kelas_kelembagaan')
                    ->get(),
                'economic_by_regency' => NilaiTransaksiEkonomi::join('m_regencies', 'nilai_transaksi_ekonomi.regency_id', '=', 'm_regencies.id')
                    ->where('nilai_transaksi_ekonomi.year', $currentYear)
                    ->where('nilai_transaksi_ekonomi.status', 'final')
                    ->selectRaw('m_regencies.name as regency, sum(total_nilai_transaksi) as total')
                    ->groupBy('m_regencies.name')
                    ->pluck('total', 'regency'),
                'top_commodities' => NilaiTransaksiEkonomiDetail::join('m_commodities', 'nilai_transaksi_ekonomi_details.commodity_id', '=', 'm_commodities.id')
                    ->join('nilai_transaksi_ekonomi', 'nilai_transaksi_ekonomi_details.nilai_transaksi_ekonomi_id', '=', 'nilai_transaksi_ekonomi.id')
                    ->where('nilai_transaksi_ekonomi.year', $currentYear)
                    ->where('nilai_transaksi_ekonomi.status', 'final')
                    ->selectRaw('m_commodities.name as commodity, sum(nilai_transaksi_ekonomi_details.nilai_transaksi) as total')
                    ->groupBy('m_commodities.name')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->pluck('total', 'commodity'),
                'top_groups' => NilaiTransaksiEkonomi::where('year', $currentYear)
                    ->where('status', 'final')
                    ->selectRaw('nama_kth as group_name, sum(total_nilai_transaksi) as total')
                    ->groupBy('nama_kth')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->pluck('total', 'group_name')
            ];
        });

        return array_merge($staticStats, $yearlyStats);
    }


    /**
     * Export Rehabilitasi Lahan data to Excel
     */
    public function exportRehabLahan(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $filename = 'laporan-rehabilitasi-lahan-' . $year . '-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new RehabLahanExport($year), $filename);
    }

    /**
     * Fill missing months with 0
     */
    private function fillMonths($data)
    {
        $filledData = [];
        for ($i = 1; $i <= 12; $i++) {
            $filledData[$i] = (float) ($data[$i] ?? 0);
        }
        return $filledData;
    }
}
