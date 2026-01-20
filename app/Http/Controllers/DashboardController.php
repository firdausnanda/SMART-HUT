<?php

namespace App\Http\Controllers;

use App\Models\HasilHutanKayu;
use App\Models\Kups;
use App\Models\RealisasiPnbp;
use App\Models\RehabLahan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;
        $chartYear = $request->input('year', $currentYear);

        // --- Rehab Lahan Stats (Existing) ---
        $totalRehabCurrent = RehabLahan::where('year', $currentYear)->where('status', 'final')->sum('realization');
        $totalRehabPrev = RehabLahan::where('year', $prevYear)->where('status', 'final')->sum('realization');

        $rehabGrowth = 0;
        if ($totalRehabPrev > 0) {
            $rehabGrowth = (($totalRehabCurrent - $totalRehabPrev) / $totalRehabPrev) * 100;
        } elseif ($totalRehabCurrent > 0) {
            $rehabGrowth = 100;
        }

        // --- Produksi Kayu Stats (New) ---
        // Sum annual_volume_target for final records. The column is string, so we cast to float.
        $kayuCurrent = HasilHutanKayu::where('year', $currentYear)
            ->where('status', 'final')
            ->get()
            ->sum(function ($row) {
                return (float) $row->annual_volume_target;
            });

        // --- Transaksi Ekonomi (PNBP) Stats (New) ---
        // Sum PSDH + DBHDR
        $pnbpCurrent = RealisasiPnbp::where('year', $currentYear)
            ->where('status', 'final')
            ->get()
            ->sum(function ($row) {
                return (float) str_replace(['Rp', '.', ' '], '', $row->number_of_psdh) +
                    (float) str_replace(['Rp', '.', ' '], '', $row->number_of_dbhdr);
                // Note: Assuming the data might have formatting characters based on 'string' type in validation. 
                // If it's pure number string, these replaces are harmless.
            });

        // --- KUPS Stats (New) ---
        // Total accumulated KUPS
        $kupsTotal = Kups::count();
        $kupsActive = Kups::where('status', 'active')->count(); // Assuming there's a status 'active', otherwise just use total.
        // Actually Kups model has 'status' but values might be 'final', 'verified' etc. Let's just use total count for now or based on implementation plan.
        // Plan said: "Count of Kups records".

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

        // --- Available Years ---
        $availableYears = RehabLahan::selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Merge with other models' years if needed, but for now RehabLahan is the main driver for the chart.
        if (!in_array(date('Y'), $availableYears)) {
            $availableYears[] = (int) date('Y');
            rsort($availableYears);
        }

        // --- Recent Activity ---
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
                    // Fetch the first role name (if any), capitalize it.
                    'role' => $activity->causer && $activity->causer->roles->isNotEmpty()
                        ? $activity->causer->roles->first()->description
                        : '-',
                    'created_at' => $activity->created_at->diffForHumans(),
                    'subject_type' => class_basename($activity->subject_type),
                    'event' => $activity->event,
                ];
            });

        // --- KUPS Chart Data ---
        $kupsByClass = Kups::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get();

        $kupsChart = [
            'labels' => $kupsByClass->pluck('category'),
            'data' => $kupsByClass->pluck('total'),
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'rehabilitation' => [
                    'total' => $totalRehabCurrent,
                    'growth' => round($rehabGrowth, 1),
                ],
                'wood_production' => [
                    'total' => $kayuCurrent,
                ],
                'economy' => [
                    'total' => $pnbpCurrent,
                ],
                'kups' => [
                    'total' => $kupsTotal,
                ]
            ],
            'chartData' => $chartData,
            'kupsChart' => $kupsChart,
            'filters' => [
                'year' => (int) $chartYear
            ],
            'availableYears' => $availableYears,
            'recentActivities' => $activities
        ]);
    }
}
