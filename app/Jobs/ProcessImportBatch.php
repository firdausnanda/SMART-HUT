<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(
        public readonly string $batchId,
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $batch = ImportBatch::findOrFail($this->batchId);

        // Guard: only process if still in processing state
        if ($batch->status !== 'processing') {
            Log::warning("[ProcessImportBatch] Batch {$this->batchId} is not in processing state (status: {$batch->status}). Skipping.");
            return;
        }

        try {
            // Delegate to the module-specific processor registered in the batch
            // The processor class name is stored in batch metadata
            $processorClass = $this->resolveProcessor($batch->module_name);

            if (!$processorClass) {
                throw new \RuntimeException("No processor found for module: {$batch->module_name}");
            }

            $processor = app($processorClass);
            $importedCount = $processor->process($batch);

            $batch->update([
                'status' => 'completed',
                'imported_count' => $importedCount,
            ]);

            Log::info("[ProcessImportBatch] Batch {$this->batchId} completed. Imported: {$importedCount} rows.");

        } catch (\Throwable $e) {
            Log::error("[ProcessImportBatch] Batch {$this->batchId} failed: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Map module_name prefixes to their processor class.
     * Module names may have a suffix like 'hasil-hutan-kayu|Hutan Negara'
     */
    private function resolveProcessor(string $moduleName): ?string
    {
        $prefix = explode('|', $moduleName)[0];

        return match ($prefix) {
            'hasil-hutan-kayu'          => \App\Services\Imports\Processors\HasilHutanKayuProcessor::class,
            'hhbk'                      => \App\Services\Imports\Processors\HasilHutanBukanKayuProcessor::class,
            'pbphh'                     => \App\Services\Imports\Processors\PbphhProcessor::class,
            'kebakaran-hutan'           => \App\Services\Imports\Processors\KebakaranHutanProcessor::class,
            'nilai-ekonomi'             => \App\Services\Imports\Processors\NilaiEkonomiProcessor::class,
            'nilai-transaksi-ekonomi'   => \App\Services\Imports\Processors\NilaiTransaksiEkonomiProcessor::class,
            'penghijauan-lingkungan'    => \App\Services\Imports\Processors\PenghijauanLingkunganProcessor::class,
            'pengunjung-wisata'         => \App\Services\Imports\Processors\PengunjungWisataProcessor::class,
            'perkembangan-kth'          => \App\Services\Imports\Processors\PerkembanganKthProcessor::class,
            'realisasi-pnbp'            => \App\Services\Imports\Processors\RealisasiPnbpProcessor::class,
            'reboisasi-ps'              => \App\Services\Imports\Processors\ReboisasiPsProcessor::class,
            'rehab-lahan'               => \App\Services\Imports\Processors\RehabLahanProcessor::class,
            'rehab-manggrove'           => \App\Services\Imports\Processors\RehabManggroveProcessor::class,
            'rhl-teknis'                => \App\Services\Imports\Processors\RhlTeknisProcessor::class,
            'skps'                      => \App\Services\Imports\Processors\SkpsProcessor::class,
            'kups'                      => \App\Services\Imports\Processors\KupsProcessor::class,
            default                     => null,
        };
    }
}
