<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekapKepegawaianService;

class GenerateRekapKepegawaian extends Command
{
    protected $signature = 'rekap:kepegawaian {year?} {month?} {--force} {--only-statistik}';
    protected $description = 'Generate rekap bulanan data kepegawaian';

    public function handle(RekapKepegawaianService $service)
    {
        $year = $this->argument('year') ?? now()->year;
        $month = $this->argument('month') ?? now()->month;
        $force = $this->option('force');
        $onlyStatistik = $this->option('only-statistik');

        $this->info("Memulai generate rekap kepegawaian untuk periode {$month}-{$year}...");

        if ($onlyStatistik) {
            $service->recalculateStatistik($year, $month, 'scheduled');
            $this->info("Statistik berhasil dihitung ulang.");
            return 0;
        }

        $service->generate($year, $month, 'scheduled');

        $this->info("✅ Rekap Kepegawaian periode {$month}-{$year} berhasil digenerate.");
        
        return 0;
    }
}
