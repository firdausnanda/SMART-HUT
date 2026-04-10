<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pegawai;
use App\Models\RiwayatKgb;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAutomatedKgb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kgb:process-automated';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically process KGB records for employees whose salary increase is due.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automated KGB processing...');

        $pegawais = Pegawai::where('status', 'final')
            ->where('status_kedudukan', 'Aktif')
            ->get();

        $processedCount = 0;
        $today = Carbon::today();

        foreach ($pegawais as $pegawai) {
            // Get the latest KGB record
            $latestKgb = $pegawai->riwayatKgb()
                ->where('status', 'final')
                ->latest('tmt_kgb')
                ->first();

            if (!$latestKgb || !$latestKgb->tmt_kgb_berikutnya) {
                // Skip if no previous salary data exists to apply the 8% increase
                continue;
            }

            $dueDate = Carbon::parse($latestKgb->tmt_kgb_berikutnya);

            // Check if it's time (due date is today or in the past)
            if ($dueDate->lessThanOrEqualTo($today)) {
                
                // Check if a record already exists for this TMT to avoid duplicates
                $exists = RiwayatKgb::where('pegawai_id', $pegawai->id)
                    ->where('tmt_kgb', $dueDate->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::beginTransaction();
                try {
                    $newSalary = round($latestKgb->gaji_pokok_baru * 1.08);
                    $newKgb = RiwayatKgb::create([
                        'pegawai_id' => $pegawai->id,
                        'no_sk' => 'AUTO/KGB/' . $today->format('Ymd') . '-' . $pegawai->nip,
                        'tanggal_sk' => $today->toDateString(),
                        'tmt_kgb' => $dueDate->toDateString(),
                        'gaji_pokok_baru' => $newSalary,
                        'tmt_kgb_berikutnya' => $dueDate->copy()->addYears(2)->toDateString(),
                        'status' => 'final',
                    ]);

                    DB::commit();
                    $processedCount++;
                    $this->info("Processed KGB for: {$pegawai->nama_lengkap} (NIP: {$pegawai->nip})");
                    Log::info("Automated KGB created for {$pegawai->nip}. New Salary: {$newSalary}");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Failed to process KGB for {$pegawai->nip}: " . $e->getMessage());
                    Log::error("KGB Automation Error for {$pegawai->nip}: " . $e->getMessage());
                }
            }
        }

        $this->info("Processing completed. {$processedCount} records created.");
    }
}
