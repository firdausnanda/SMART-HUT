<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Auto backup database harian jam 02:00
        $schedule->call(function () {
            $backupName = config('backup.backup.name');
            try {
                \Illuminate\Support\Facades\Storage::disk('google')->makeDirectory($backupName);
            } catch (\Exception $e) {
                // Folder mungkin sudah ada, abaikan
            }

            \Illuminate\Support\Facades\Artisan::call('backup:run', ['--only-db' => true]);
        })->dailyAt('02:00');

        // Rekap Kepegawaian Bulanan setiap tanggal 1 jam 00:05
        $schedule->command('rekap:kepegawaian')->monthlyOn(1, '00:05');

        // Process automated KGB records daily at 00:01
        $schedule->command('kgb:process-automated')->dailyAt('00:01');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
