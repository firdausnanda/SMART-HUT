<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeSatuan extends Command
{
    protected $signature = 'satuan:normalize';
    protected $description = 'Normalize satuan in details tables';

    public function handle()
    {
        $this->info("Normalizing satuan in database...");

        $mapping = [
            'kg' => ['Kg', 'Kilogram (Kg)', 'Lg'],
            'm3' => ['m3', 'Meter Kubik (m3)', 'Meter Kubik (M3)'],
            'batang' => ['batang', 'Batangan', 'Bantangan', 'btg'],
            'ton' => ['Ton'],
            'pcs' => ['Pcs', 'pcs'],
            'buah' => ['Buah', 'buah'],
            'bibit' => ['Tanaman', 'bibit'],
            'stup' => ['stup'],
            'orang' => ['orang'],
            'ekor' => ['ekor'],
            'liter' => ['liter'],
            'ikat' => ['ikat'],
            'butir' => ['Butir', 'butir'],
            'lainnya' => ['-', 'Lainnya', 'lainnya'],
        ];

        DB::beginTransaction();
        try {
            foreach ($mapping as $normalized => $variations) {
                // Update nilai_transaksi_ekonomi_details
                DB::table('nilai_transaksi_ekonomi_details')
                    ->whereIn('satuan', $variations)
                    ->update(['satuan' => $normalized]);

                // Update nilai_ekonomi_details
                DB::table('nilai_ekonomi_details')
                    ->whereIn('satuan', $variations)
                    ->update(['satuan' => $normalized]);
            }
            
            // For any remaining values not in the mapping but actually exist, map to lowercase if possible
            // We just update all to lowercase anyway to be safe? 
            // Better to just update anything that is not in the recognized list to "lainnya"
            
            $validSatuans = array_keys($mapping);
            
            DB::table('nilai_transaksi_ekonomi_details')
                ->whereNotIn('satuan', $validSatuans)
                ->update(['satuan' => 'lainnya']);
                
            DB::table('nilai_ekonomi_details')
                ->whereNotIn('satuan', $validSatuans)
                ->update(['satuan' => 'lainnya']);

            DB::commit();
            $this->info("Normalization completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
        }
    }
}
