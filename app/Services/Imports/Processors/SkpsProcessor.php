<?php

namespace App\Services\Imports\Processors;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;

class SkpsProcessor extends BaseImportProcessor
{
    public function process(ImportBatch $batch): int
    {
        return parent::process($batch);
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        // TODO: implement logic
        return true;
    }
}
