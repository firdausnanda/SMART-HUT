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

    $query = Activity::query()->with('causer');

    if ($request->sort) {
      if ($request->sort === 'causer_id') {
        // Sorting by user id
        $query->orderBy('causer_id', $request->direction ?? 'asc');
      } else {
        $query->orderBy($request->sort, $request->direction ?? 'asc');
      }
    } else {
      $query->orderBy('created_at', 'desc');
    }

    if ($request->search) {
      $query->where(function ($q) use ($request) {
        $q->where('description', 'like', '%' . $request->search . '%')
          ->orWhere('subject_type', 'like', '%' . $request->search . '%')
          ->orWhereHas('causer', function ($qCAuser) use ($request) {
            $qCAuser->where('name', 'like', '%' . $request->search . '%');
          });
      });
    }

    $perPage = $request->per_page ? $request->per_page : 10;
    $activities = $query->paginate($perPage)->withQueryString();

    return Inertia::render('ActivityLog/Index', [
      'activities' => $activities,
      'filters' => $request->only(['search', 'per_page']),
    ]);
  }
}
