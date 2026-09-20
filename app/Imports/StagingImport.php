<?php

namespace App\Imports;

use App\Models\ImportStagingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class StagingImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function chunkSize(): int
    {
        return 200;
    }
    protected $batchId;
    protected $validatorCallback;
    protected $rowNumber = 1;

    public function __construct(string $batchId, callable $validatorCallback = null)
    {
        $this->batchId = $batchId;
        $this->validatorCallback = $validatorCallback;
    }

    public function collection(Collection $rows)
    {
        $stagingRows = [];
        $now = now();

        foreach ($rows as $row) {
            $this->rowNumber++;
            
            // Skip completely empty rows
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $status = "valid";
            $errors = null;

            if ($this->validatorCallback) {
                $validationResult = call_user_func($this->validatorCallback, $row->toArray(), $this->rowNumber);
                if ($validationResult !== true) {
                    $status = "invalid";
                    $errors = is_array($validationResult) ? json_encode($validationResult) : json_encode([$validationResult]);
                }
            }

            $stagingRows[] = [
                "import_batch_id" => $this->batchId,
                "row_number" => $this->rowNumber,
                "data_payload" => $row->toJson(),
                "status" => $status,
                "validation_errors" => $errors,
                "created_at" => $now,
                "updated_at" => $now,
            ];
        }

        foreach (array_chunk($stagingRows, 500) as $chunk) {
            ImportStagingRow::insert($chunk);
        }
    }
}

