<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    // Advanced Search Filters
    if ($request->user_id) {
      $query->where('causer_id', $request->user_id);
    }

    if ($request->action) {
      $query->where('description', $request->action);
    }

    if ($request->subject_type) {
      $query->where('subject_type', $request->subject_type);
    }

    if ($request->subject_id) {
      $query->where('subject_id', $request->subject_id);
    }

    if ($request->date_start) {
      $query->whereDate('created_at', '>=', $request->date_start);
    }

    if ($request->date_end) {
      $query->whereDate('created_at', '<=', $request->date_end);
    }

    $perPage = $request->per_page ? $request->per_page : 10;
    $activities = $query->paginate($perPage)->withQueryString();

    // Data for Advanced Search Options
    $users = User::select('id', 'name')->orderBy('name')->get();
    $subjectTypes = Activity::select('subject_type')->whereNotNull('subject_type')->distinct()->pluck('subject_type')->map(function ($type) {
      return [
        'label' => str_replace('App\\Models\\', '', $type),
        'value' => $type
      ];
    });
    $actions = Activity::select('description')->distinct()->pluck('description');

    return Inertia::render('ActivityLog/Index', [
      'activities' => $activities,
      'filters' => $request->all(['search', 'per_page', 'sort', 'direction', 'user_id', 'action', 'subject_type', 'subject_id', 'date_start', 'date_end']),
      'options' => [
        'users' => $users,
        'subject_types' => $subjectTypes,
        'actions' => $actions,
      ]
    ]);
  }
}
