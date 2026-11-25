<?php

namespace Modules\Penanaman\App\Services\Imports\Processors;

use App\Models\ImportBatch;
use App\Models\Penanaman;
use App\Services\Imports\Processors\BaseImportProcessor;
use Modules\Penanaman\App\Services\Imports\PenanamanImportValidator;
use RuntimeException;

class PenanamanProcessor extends BaseImportProcessor
{
    private ?PenanamanImportValidator $validator = null;

    protected function bootReferenceCache(): void
    {
    }

    protected function processRow(array $row, ImportBatch $batch): mixed
    {
        $user = $batch->user;
        if (!$user || !$user->hasRole(['admin', 'admin_provinsi', 'operator_kabupaten'])) {
            throw new RuntimeException('Akun pengimpor tidak memiliki akses Penanaman.');
        }
        if ($user->hasRole('operator_kabupaten') && !$user->regency_id) {
            throw new RuntimeException('Wilayah akun pengimpor belum ditetapkan.');
        }

        $this->validator ??= new PenanamanImportValidator(
            $user->hasRole(['admin', 'admin_provinsi']) ? null : (string) $user->regency_id
        );
        $data = $this->validator->resolve($row);

        Penanaman::create($data + [
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return true;
    }
}
