<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Commodity;
use App\Models\NilaiTransaksiEkonomiDetail;
use App\Models\NilaiEkonomiDetail;
use Illuminate\Support\Facades\DB;

class MergeCommodities extends Command
{
    protected $signature = 'commodities:merge';
    protected $description = 'Merge duplicate commodities and delete specific commodities.';

    public function handle()
    {
        DB::beginTransaction();
        try {
            // 1. Merge duplicates and unwanted commodities into specific names
            $this->info("Merging commodities...");
            $mapping = [
                'Buah-Buahan Segar' => ['Buah - buahan segar', 'Buah -buahan', 'Buah- buahan', 'Buah2segar', 'Buah2 segar'],
                'Minyak Atsiri' => ['Minyak Astiri'],
                'Kerajinan Bambu' => ['Kerajinan Berbahan Bambu'],
                'Lainnya' => ['kg', 'hhk', 'hasil', 'porang', 'pembibitan', 'reyeng', 'getah pinus', 'jahe', 'Lainnya'],
            ];

            foreach ($mapping as $canonicalName => $variations) {
                foreach ([0, 1] as $isTransaksiEkonomi) {
                    $canonical = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
                        ->where('is_nilai_transaksi_ekonomi', $isTransaksiEkonomi)
                        ->where('name', $canonicalName)
                        ->first();
                    
                    if (!$canonical) {
                        $variationsExist = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
                            ->where('is_nilai_transaksi_ekonomi', $isTransaksiEkonomi)
                            ->whereIn('name', $variations)
                            ->exists();
                        
                        if ($variationsExist) {
                            $canonical = Commodity::create([
                                'name' => $canonicalName,
                                'is_nilai_transaksi_ekonomi' => $isTransaksiEkonomi,
                            ]);
                            $this->info("Created canonical: {$canonicalName} for scope {$isTransaksiEkonomi}");
                        }
                    }

                    if ($canonical) {
                        $oldCommodities = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
                            ->where('is_nilai_transaksi_ekonomi', $isTransaksiEkonomi)
                            ->whereIn('name', $variations)
                            ->where('id', '!=', $canonical->id)
                            ->get();

                        foreach ($oldCommodities as $old) {
                            $this->info("Merging {$old->name} -> {$canonical->name}");
                            NilaiTransaksiEkonomiDetail::where('commodity_id', $old->id)->update(['commodity_id' => $canonical->id]);
                            if (class_exists(NilaiEkonomiDetail::class)) {
                                NilaiEkonomiDetail::where('commodity_id', $old->id)->update(['commodity_id' => $canonical->id]);
                            }
                            $old->delete();
                        }
                    }
                }
            }

            DB::commit();
            $this->info("Process completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
        }
    }
}
