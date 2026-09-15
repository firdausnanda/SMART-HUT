<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\Satuan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $validUnits = array_map(fn($case) => $case->value, Satuan::cases());

        DB::table('hasil_hutan_bukan_kayu_details')->orderBy('id')->chunk(100, function ($details) use ($validUnits) {
            foreach ($details as $detail) {
                $oldUnit = $detail->unit;
                $newUnit = strtolower(trim((string)$oldUnit));

                // If not in enum, default to 'lainnya'
                if (!in_array($newUnit, $validUnits)) {
                    $newUnit = 'lainnya';
                }

                if ($oldUnit !== $newUnit) {
                    DB::table('hasil_hutan_bukan_kayu_details')
                        ->where('id', $detail->id)
                        ->update(['unit' => $newUnit]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing this precisely is hard because we lost the original casing or non-standard values.
        // But normally 'kg' was 'Kg', so we can attempt a basic rollback.
        DB::table('hasil_hutan_bukan_kayu_details')
            ->where('unit', 'kg')
            ->update(['unit' => 'Kg']);
    }
};
