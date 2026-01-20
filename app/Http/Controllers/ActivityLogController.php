<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
  public function index(Request $request)
  {
    abort_unless($request->user()->hasRole('admin'), 403);

    $query = Activity::query()
      ->with('causer')
      ->orderBy('created_at', 'desc');

    if ($request->search) {
      $query->where('description', 'like', '%' . $request->search . '%')
        ->orWhereHas('causer', function ($q) use ($request) {
          $q->where('name', 'like', '%' . $request->search . '%');
        });
    }

    $activities = $query->paginate(10)->withQueryString();

    return Inertia::render('ActivityLog/Index', [
      'activities' => $activities,
      'filters' => $request->only(['search']),
    ]);
  }
}
