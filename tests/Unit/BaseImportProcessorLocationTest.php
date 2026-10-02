<?php

namespace Tests\Unit;

use App\Models\ImportBatch;
use App\Services\Imports\Processors\BaseImportProcessor;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class BaseImportProcessorLocationTest extends TestCase
{
    private function processor(): BaseImportProcessor
    {
        return new class extends BaseImportProcessor {
            public function seedDistricts(array $districts): void
            {
                static::$districts = new Collection($districts);
            }

            public function district(string $name, int $regencyId): ?object
            {
                return $this->findDistrict($name, $regencyId);
            }

            protected function processRow(array $row, ImportBatch $batch): mixed
            {
                return false;
            }
        };
    }

    public function test_district_match_accepts_numeric_string_regency_ids_from_mysql(): void
    {
        $processor = $this->processor();
        $processor->seedDistricts([
            (object) ['id' => '3174040', 'regency_id' => '3174', 'name' => 'GROGOL PETAMBURAN'],
            (object) ['id' => '3506220', 'regency_id' => '3506', 'name' => 'GROGOL'],
        ]);

        $this->assertSame('3506220', $processor->district('Grogol', 3506)?->id);
    }

    public function test_district_match_never_uses_another_regency_as_fallback(): void
    {
        $processor = $this->processor();
        $processor->seedDistricts([
            (object) ['id' => '3174040', 'regency_id' => '3174', 'name' => 'GROGOL PETAMBURAN'],
        ]);

        $this->assertNull($processor->district('Grogol', 3506));
    }
}
