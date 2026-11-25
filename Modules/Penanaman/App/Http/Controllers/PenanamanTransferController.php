<?php

namespace Modules\Penanaman\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\StagingImport;
use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Penanaman\App\Exports\PenanamanExport;
use Modules\Penanaman\App\Exports\PenanamanTemplateExport;
use Modules\Penanaman\App\Services\Imports\PenanamanImportValidator;
use Throwable;

class PenanamanTransferController extends Controller
{
    private function assertModuleAccess(): void
    {
        abort_unless(
            $this->user()->hasRole(['admin', 'admin_provinsi']) ||
            ($this->user()->hasRole('operator_kabupaten') && $this->user()->regency_id),
            403
        );
    }

    private function assertOwnBatch(ImportBatch $batch): void
    {
        abort_unless($batch->module_name === 'penanaman' && $batch->user_id === $this->user()->id, 404);
    }

    public function export(Request $request)
    {
        $this->assertModuleAccess();
        $filters = $request->validate([
            'year' => 'nullable|integer|between:2000,2100',
            'month' => 'nullable|integer|between:1,12',
            'status' => 'nullable|in:all,draft,final',
            'regency_id' => ['nullable', Rule::exists('m_regencies', 'id')->where('province_id', '35')],
            'fund_source' => ['nullable', 'string', Rule::exists('m_sumber_dana', 'name')],
            'search' => 'nullable|string|max:100',
            'columns' => ['sometimes', 'array', 'min:1', 'max:' . count(PenanamanExport::COLUMN_HEADINGS)],
            'columns.*' => ['string', 'distinct', Rule::in(array_keys(PenanamanExport::COLUMN_HEADINGS))],
        ]);
        if (!$this->user()->hasRole(['admin', 'admin_provinsi']) && !empty($filters['regency_id'])) {
            abort_unless((string) $filters['regency_id'] === (string) $this->user()->regency_id, 403);
        }

        return Excel::download(
            new PenanamanExport($this->user(), $filters),
            'data-penanaman-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function template()
    {
        $this->assertModuleAccess();

        return Excel::download(new PenanamanTemplateExport(), 'template-import-penanaman.xlsx');
    }

    public function previewImport(Request $request)
    {
        $this->assertModuleAccess();
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        $batch = ImportBatch::create([
            'user_id' => $this->user()->id,
            'module_name' => 'penanaman',
            'filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'pending',
        ]);

        try {
            $allowedRegency = $this->user()->hasRole(['admin', 'admin_provinsi'])
                ? null : (string) $this->user()->regency_id;
            Excel::import(
                new StagingImport($batch->id, new PenanamanImportValidator($allowedRegency)),
                $request->file('file')
            );
            if (!$batch->stagingRows()->exists()) {
                $batch->delete();
                return back()->withErrors(['file' => 'File tidak memiliki baris data.']);
            }
        } catch (Throwable $exception) {
            $batch->delete();
            Log::warning('Pratinjau import Penanaman gagal.', [
                'user_id' => $this->user()->id,
                'error' => $exception->getMessage(),
            ]);
            return back()->withErrors(['file' => 'File tidak dapat dibaca. Periksa template dan formatnya.']);
        }

        return redirect()->route('penanaman.show-preview', $batch->id);
    }

    public function showPreview(ImportBatch $batch)
    {
        $this->assertModuleAccess();
        $this->assertOwnBatch($batch);

        return Inertia::render('Penanaman/ImportPreview', [
            'batch' => $batch,
            'rows' => $batch->stagingRows()->paginate(50),
            'validCount' => $batch->stagingRows()->where('status', 'valid')->count(),
        ]);
    }

    public function commitImport(ImportBatch $batch)
    {
        $this->assertModuleAccess();
        $this->assertOwnBatch($batch);
        abort_unless($batch->status === 'pending', 409);
        if (!$batch->stagingRows()->where('status', 'valid')->exists()) {
            return back()->withErrors(['import' => 'Tidak ada baris valid untuk diproses.']);
        }

        $batch->update(['status' => 'processing']);
        ProcessImportBatch::dispatch($batch->id);

        return back();
    }
}
