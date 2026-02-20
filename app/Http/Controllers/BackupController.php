<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
  /**
   * Display a listing of all backups.
   */
  public function index()
  {
    $this->authorizeAdmin();

    $backups = [];

    try {
      $disk = Storage::disk('google');
      $files = $disk->allFiles('/');

      foreach ($files as $file) {
        // Only show zip files
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'zip') {
          continue;
        }

        try {
          $backups[] = [
            'path' => $file,
            'name' => basename($file),
            'size' => $this->formatBytes($disk->size($file)),
            'size_raw' => $disk->size($file),
            'date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
            'age' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($file))->diffForHumans(),
          ];
        } catch (\Exception $e) {
          // Skip files that can't be read
          continue;
        }
      }

      // Sort by date descending (newest first)
      usort($backups, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
    } catch (\Exception $e) {
      // If Google Drive is not configured, just show empty list
      session()->flash('error', 'Tidak dapat terhubung ke Google Drive. Pastikan konfigurasi sudah benar: ' . $e->getMessage());
    }

    return Inertia::render('Backup/Index', [
      'backups' => $backups,
    ]);
  }

  /**
   * Create a new backup.
   */
  public function create(Request $request)
  {
    $this->authorizeAdmin();

    try {
      // Run backup (database only, to google drive)
      Artisan::call('backup:run', [
        '--only-db' => true,
        '--only-to-disk' => 'google',
        '--disable-notifications' => true,
      ]);

      $output = Artisan::output();

      return redirect()->route('backups.index')
        ->with('success', 'Backup database berhasil dibuat dan disimpan ke Google Drive.');
    } catch (\Exception $e) {
      return redirect()->route('backups.index')
        ->with('error', 'Gagal membuat backup: ' . $e->getMessage());
    }
  }

  /**
   * Download a backup file.
   */
  public function download($filename)
  {
    $this->authorizeAdmin();

    try {
      $disk = Storage::disk('google');

      // Find the file (could be in subdirectory)
      $files = $disk->allFiles('/');
      $filePath = null;

      foreach ($files as $file) {
        if (basename($file) === $filename) {
          $filePath = $file;
          break;
        }
      }

      if (!$filePath || !$disk->exists($filePath)) {
        return redirect()->route('backups.index')
          ->with('error', 'File backup tidak ditemukan.');
      }

      $stream = $disk->readStream($filePath);

      return response()->stream(function () use ($stream) {
        fpassthru($stream);
        if (is_resource($stream)) {
          fclose($stream);
        }
      }, 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      ]);
    } catch (\Exception $e) {
      return redirect()->route('backups.index')
        ->with('error', 'Gagal mengunduh backup: ' . $e->getMessage());
    }
  }

  /**
   * Delete a backup file.
   */
  public function destroy($filename)
  {
    $this->authorizeAdmin();

    try {
      $disk = Storage::disk('google');

      // Find the file (could be in subdirectory)
      $files = $disk->allFiles('/');
      $filePath = null;

      foreach ($files as $file) {
        if (basename($file) === $filename) {
          $filePath = $file;
          break;
        }
      }

      if (!$filePath || !$disk->exists($filePath)) {
        return redirect()->route('backups.index')
          ->with('error', 'File backup tidak ditemukan.');
      }

      $disk->delete($filePath);

      return redirect()->route('backups.index')
        ->with('success', 'Backup berhasil dihapus.');
    } catch (\Exception $e) {
      return redirect()->route('backups.index')
        ->with('error', 'Gagal menghapus backup: ' . $e->getMessage());
    }
  }

  /**
   * Check if the current user is an admin.
   */
  private function authorizeAdmin()
  {
    if (!auth()->user() || !auth()->user()->hasRole('admin')) {
      abort(403, 'Unauthorized. Only administrators can manage backups.');
    }
  }

  /**
   * Format bytes to human-readable format.
   */
  private function formatBytes($bytes, $precision = 2)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }
}
