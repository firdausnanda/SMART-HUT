<?php

namespace App\Http\Controllers;

use App\Models\NilaiTransaksiEkonomi;
use App\Models\Commodity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class NilaiTransaksiEkonomiController extends Controller
{
  use \App\Traits\HandlesImportFailures;

  public function __construct()
  {
    $this->middleware('permission:pemberdayaan.view')->only(['index', 'show']);
    $this->middleware('permission:pemberdayaan.create')->only(['create', 'store']);
    $this->middleware('permission:pemberdayaan.create|pemberdayaan.edit')->only(['edit', 'update']); // submit removed
    $this->middleware('permission:pemberdayaan.delete')->only(['destroy']);
    $this->middleware('permission:pemberdayaan.approve')->only(['verify', 'approve', 'reject']);
  }

  // ... (index through destroy unchanged)

  public function destroy(NilaiTransaksiEkonomi $nilaiTransaksiEkonomi)
  {
    $nilaiTransaksiEkonomi->delete();
    return redirect()->route('nilai-transaksi-ekonomi.index')->with('success', 'Data transaksi berhasil dihapus.');
  }

  public function submit(NilaiTransaksiEkonomi $nilaiTransaksiEkonomi)
  {
    // Custom authorization: Allow if user is creator OR has specific permissions
    if (auth()->id() !== $nilaiTransaksiEkonomi->created_by && !auth()->user()->can('pemberdayaan.edit') && !auth()->user()->can('pemberdayaan.create')) {
      return redirect()->back()->with('error', 'Akses Ditolak: Anda tidak memiliki izin untuk melakukan aksi ini.');
    }

    $nilaiTransaksiEkonomi->update(['status' => 'waiting_kasi']);
    return redirect()->back()->with('success', 'Laporan berhasil diajukan untuk verifikasi Kasi.');
  }

  public function approve(NilaiTransaksiEkonomi $nilaiTransaksiEkonomi)
  {
    $user = auth()->user();

    if (($user->hasRole('kasi') || $user->hasRole('admin')) && $nilaiTransaksiEkonomi->status === 'waiting_kasi') {
      $nilaiTransaksiEkonomi->update(['status' => 'waiting_cdk', 'approved_by_kasi_at' => now()]);
      return redirect()->back()->with('success', 'Laporan disetujui dan diteruskan ke KaCDK.');
    }

    if (($user->hasRole('kacdk') || $user->hasRole('admin')) && $nilaiTransaksiEkonomi->status === 'waiting_cdk') {
      $nilaiTransaksiEkonomi->update(['status' => 'final', 'approved_by_cdk_at' => now()]);
      return redirect()->back()->with('success', 'Laporan telah disetujui secara final.');
    }

    return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
  }

  public function reject(Request $request, NilaiTransaksiEkonomi $nilaiTransaksiEkonomi)
  {
    $request->validate(['rejection_note' => 'required|string|max:255']);
    $nilaiTransaksiEkonomi->update(['status' => 'rejected', 'rejection_note' => $request->rejection_note]);
    return redirect()->back()->with('success', 'Laporan telah ditolak dengan catatan.');
  }

  public function export(Request $request)
  {
    $year = $request->query('year');
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\NilaiTransaksiEkonomiExport($year), 'nilai-transaksi-ekonomi-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\NilaiTransaksiEkonomiTemplateExport, 'template_import_nilai_transaksi_ekonomi.xlsx');
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
    $import = new \App\Imports\NilaiTransaksiEkonomiImport();

    try {
      \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($e->failures()));
    }

    if ($import->failures()->isNotEmpty()) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($import->failures()));
    }

    return redirect()->back()->with('success', 'Data berhasil diimport.');
  }
}
