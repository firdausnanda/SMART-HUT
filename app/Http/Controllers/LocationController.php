<?php

namespace App\Http\Controllers;

use App\Models\Regencies;
use App\Models\Districts;
use App\Models\Villages;
use Illuminate\Http\Request;

class LocationController extends Controller
{
  public function getRegencies($provinceId)
  {
    $query = Regencies::where('province_id', $provinceId);

    if (auth()->check()) {
      $user = auth()->user();
      if ($user->cdk_id) {
        $query->whereHas('cdks', function ($q) use ($user) {
          $q->where('cdks.id', $user->cdk_id);
        });
      }
    }

    $regencies = $query->orderBy('name', 'asc')
      ->get(['id', 'name']);

    return response()->json($regencies);
  }

  public function getDistricts($regencyId)
  {
    $districts = Districts::where('regency_id', $regencyId)
      ->orderBy('name', 'asc')
      ->get(['id', 'name']);

    return response()->json($districts);
  }

  public function getVillages($districtId)
  {
    $villages = Villages::where('district_id', $districtId)
      ->orderBy('name', 'asc')
      ->get(['id', 'name']);

    return response()->json($villages);
  }
}
