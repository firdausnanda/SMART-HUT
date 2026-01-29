<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Models\RehabLahan;
use App\Models\PenghijauanLingkungan;
use App\Models\RehabManggrove;
use App\Models\ReboisasiPS;
use App\Models\RhlTeknis;
use App\Models\HasilHutanKayu;
use App\Models\HasilHutanBukanKayu;
use App\Models\Pbphh;
use App\Models\RealisasiPnbp;
use App\Models\Skps;
use App\Models\Kups;
use App\Models\NilaiEkonomi;
use App\Models\PerkembanganKth;
use App\Models\NilaiTransaksiEkonomi;
use App\Models\KebakaranHutan;
use App\Models\PengunjungWisata;

class WelcomeController extends Controller
{
  public function index()
  {
    $totalData = 0;

    // Aggregate counts from all major models
    $totalData += RehabLahan::count();
    $totalData += PenghijauanLingkungan::count();
    $totalData += RehabManggrove::count();
    $totalData += ReboisasiPS::count();
    $totalData += RhlTeknis::count();
    $totalData += HasilHutanKayu::count();
    $totalData += HasilHutanBukanKayu::count();
    $totalData += Pbphh::count();
    $totalData += RealisasiPnbp::count();
    $totalData += Skps::count();
    $totalData += Kups::count();
    $totalData += NilaiEkonomi::count();
    $totalData += PerkembanganKth::count();
    $totalData += NilaiTransaksiEkonomi::count();
    $totalData += KebakaranHutan::count();
    $totalData += PengunjungWisata::count();

    return Inertia::render('Welcome', [
      'canLogin' => Route::has('login'),
      'canRegister' => Route::has('register'),
      'laravelVersion' => Application::VERSION,
      'phpVersion' => PHP_VERSION,
      'totalData' => $totalData,
    ]);
  }
}
