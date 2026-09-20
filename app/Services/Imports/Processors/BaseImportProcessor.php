<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;

abstract class BaseImportProcessor
{
    /**
     * Process all valid staging rows from the batch and return count of imported records.
     */
    public function process(ImportBatch $batch): int
    {
        // Pre-load reference data ONCE for all rows (eliminates N+1 lookups)
        $this->bootReferenceCache();

        $imported = 0;

        // Chunk through staging rows to avoid loading all 5000 rows into memory at once
        $batch->stagingRows()
            ->where('status', 'valid')
            ->chunkById(200, function ($rows) use ($batch, &$imported) {
                DB::transaction(function () use ($rows, $batch, &$imported) {
                    foreach ($rows as $stagingRow) {
                        $result = $this->processRow($stagingRow->data_payload, $batch);
                        if ($result !== false) {
                            $imported++;
                        }
                    }
                });

                // Update progress so frontend polling can show progress
                $batch->update(['imported_count' => $imported]);
            });

        if (method_exists($this, 'afterProcess')) {
            $this->afterProcess($batch, $imported);
        }

        return $imported;
    }

    /**
     * Boot reference data caches (called once before processing rows).
     * Override in subclasses to pre-load lookup tables.
     */
    protected function bootReferenceCache(): void
    {
        // Default: load common geographic references
        if (!isset(static::$regencies)) {
            static::$regencies = DB::table('m_regencies')
                ->where('province_id', 35)
                ->get();
        }
        if (!isset(static::$districts)) {
            static::$districts = DB::table('m_districts')->get();
        }
    }

    /**
     * Find regency by partial name match from cached data.
     */
    protected function findRegency(string $name): ?object
    {
        $nameLower = strtolower(trim($name));
        foreach (static::$regencies as $regency) {
            if (str_contains(strtolower($regency->name), $nameLower)) {
                return $regency;
            }
        }
        return null;
    }

    /**
     * Find district by partial name match, optionally filtered by regency.
     */
    protected function findDistrict(string $name, ?int $regencyId = null): ?object
    {
        $nameLower = strtolower(trim($name));
        foreach (static::$districts as $district) {
            if ($regencyId && $district->regency_id !== $regencyId) continue;
            if (str_contains(strtolower($district->name), $nameLower)) {
                return $district;
            }
        }
        // Fallback: search without regency constraint
        if ($regencyId) {
            return $this->findDistrict($name, null);
        }
        return null;
    }

    /**
     * Process a single row. Return false to skip the row, any other value counts as imported.
     *
     * @param array $row The data_payload from import_staging_rows
     * @param ImportBatch $batch The current batch
     * @return mixed Return false to skip, anything else = success
     */
    abstract protected function processRow(array $row, ImportBatch $batch): mixed;

    // Shared static caches (reset per job instance via static properties per class)
    protected static $regencies = null;
    protected static $districts = null;
}
