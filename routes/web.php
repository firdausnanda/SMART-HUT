<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BangunanKtaController;
use App\Http\Controllers\BukanKayuController;
use App\Http\Controllers\CommodityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\HasilHutanBukanKayuController;
use App\Http\Controllers\HasilHutanKayuController;
use App\Http\Controllers\JenisProduksiController;
use App\Http\Controllers\KayuController;
use App\Http\Controllers\KebakaranHutanController;
use App\Http\Controllers\KupsController;
use App\Http\Controllers\NilaiEkonomiController;
use App\Http\Controllers\NilaiTransaksiEkonomiController;
use App\Http\Controllers\PbphhController;
use App\Http\Controllers\PengelolaWisataController;
use App\Http\Controllers\PengelolaPsController;
use App\Http\Controllers\PengunjungWisataController;
use App\Http\Controllers\PerkembanganKthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\RealisasiPnbpController;
use App\Http\Controllers\RegencyController;
use App\Http\Controllers\RehabLahanController;
use App\Http\Controllers\PenghijauanLingkunganController;
use App\Http\Controllers\RehabManggroveController;
use App\Http\Controllers\RhlTeknisController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReboisasiPsController;
use App\Http\Controllers\SkemaPerhutananSosialController;
use App\Http\Controllers\SkpsController;
use App\Http\Controllers\SumberDanaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\DemografiPegawaiController;
use App\Http\Controllers\BezettingJabatanController;
use App\Http\Controllers\ProyeksiGajiController;
use App\Http\Controllers\RekapBulananController;
use App\Http\Controllers\CdkController;
use App\Http\Middleware\CheckDashboardAccess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* |-------------------------------------------------------------------------- | Web Routes |-------------------------------------------------------------------------- | | Here is where you can register web routes for your application. These | routes are loaded by the RouteServiceProvider within a group which | contains the "web" middleware group. Now create something great! | | */

Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['verified', CheckDashboardAccess::class])
        ->name('dashboard');

    // Public Dashboard Routes (Accessible by all authed users)
    Route::get('/public/dashboard', [DashboardController::class, 'publicDashboard'])->name('public.dashboard');
    Route::get('/public/dashboard-yoy', [DashboardController::class, 'publicYoYDashboard'])->name('public.dashboard-yoy');
    // Dashboard Export
    Route::get('/dashboard/export-rehab-lahan', [DashboardController::class, 'exportRehabLahan'])->name('dashboard.export-rehab-lahan');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/locations/regencies/{provinceId}', [LocationController::class, 'getRegencies'])->name('locations.regencies');
    Route::get('/locations/districts/{regencyId}', [LocationController::class, 'getDistricts'])->name('locations.districts');
    Route::get('/locations/villages/{districtId}', [LocationController::class, 'getVillages'])->name('locations.villages');

    // === REHAB LAHAN ===
    Route::controller(RehabLahanController::class)->prefix('rehab-lahan')->name('rehab-lahan.')->group(function () {
        Route::post('{rehab_lahan}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:rehab-lahan.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:rehab-lahan.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:rehab-lahan.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:rehab-lahan.import')->name('commit-import');
        
        Route::post('import', 'import')->middleware('permission:rehab-lahan.import')->name('import');
        Route::get('template', 'template')->middleware('permission:rehab-lahan.create')->name('template');
    });
    
    // === PENGHIJAUAN LINGKUNGAN ===
    Route::controller(PenghijauanLingkunganController::class)->prefix('penghijauan-lingkungan')->name('penghijauan-lingkungan.')->group(function () {
        Route::post('{penghijauan_lingkungan}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:penghijauan-lingkungan.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:penghijauan-lingkungan.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:penghijauan-lingkungan.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:penghijauan-lingkungan.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:penghijauan-lingkungan.import')->name('import');
        Route::get('template', 'template')->middleware('permission:penghijauan-lingkungan.create')->name('template');
    });
    
    // === REHAB MANGGROVE ===
    Route::controller(RehabManggroveController::class)->prefix('rehab-manggrove')->name('rehab-manggrove.')->group(function () {
        Route::post('{rehab_manggrove}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:rehab-manggrove.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:rehab-manggrove.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:rehab-manggrove.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:rehab-manggrove.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:rehab-manggrove.import')->name('import');
        Route::get('template', 'template')->middleware('permission:rehab-manggrove.create')->name('template');
    });
    
    // === RHL TEKNIS ===
    Route::controller(RhlTeknisController::class)->prefix('rhl-teknis')->name('rhl-teknis.')->group(function () {
        Route::post('{rhl_teknis}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:rhl-teknis.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:rhl-teknis.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:rhl-teknis.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:rhl-teknis.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:rhl-teknis.import')->name('import');
        Route::get('template', 'template')->middleware('permission:rhl-teknis.create')->name('template');
    });

    // === REBOISASI PS ===
    Route::controller(ReboisasiPsController::class)->prefix('reboisasi-ps')->name('reboisasi-ps.')->group(function () {
        Route::post('{reboisasi_ps}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:reboisasi-ps.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:reboisasi-ps.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:reboisasi-ps.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:reboisasi-ps.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:reboisasi-ps.import')->name('import');
        Route::get('template', 'template')->middleware('permission:reboisasi-ps.create')->name('template');
    });

    // === PENGUNJUNG WISATA ===
    Route::controller(PengunjungWisataController::class)->prefix('pengunjung-wisata')->name('pengunjung-wisata.')->group(function () {
        Route::post('{pengunjung_wisata}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:pengunjung-wisata.export')->name('export');
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:pengunjung-wisata.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:pengunjung-wisata.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:pengunjung-wisata.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:pengunjung-wisata.import')->name('import');
        Route::get('template', 'template')->middleware('permission:pengunjung-wisata.create')->name('template');
    });

    // === KEBAKARAN HUTAN ===
    Route::controller(KebakaranHutanController::class)->prefix('kebakaran-hutan')->name('kebakaran-hutan.')->group(function () {
        Route::post('{kebakaran_hutan}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:kebakaran-hutan.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:kebakaran-hutan.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:kebakaran-hutan.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:kebakaran-hutan.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:kebakaran-hutan.import')->name('import');
        Route::get('template', 'template')->middleware('permission:kebakaran-hutan.create')->name('template');
    });

    // === HASIL HUTAN KAYU ===
    Route::controller(HasilHutanKayuController::class)->prefix('hasil-hutan-kayu')->name('hasil-hutan-kayu.')->group(function () {
        Route::post('{hasil_hutan_kayu}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->name('export');
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');

        Route::post('import', 'import')->name('import');
        Route::get('template', 'template')->name('template');
    });

    // === HASIL HUTAN BUKAN KAYU ===
    Route::controller(HasilHutanBukanKayuController::class)->prefix('hasil-hutan-bukan-kayu')->name('hasil-hutan-bukan-kayu.')->group(function () {
        Route::post('{hasil_hutan_bukan_kayu}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->name('export');
        Route::post('import-preview', 'previewImport')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->name('commit-import');

        Route::post('import', 'import')->name('import');
        Route::get('template', 'template')->name('template');
    });

    // === PBPHH ===
    Route::controller(PbphhController::class)->prefix('pbphh')->name('pbphh.')->group(function () {
        Route::post('{pbphh}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:pbphh.export')->name('export');
        Route::post('import-preview', 'previewImport')->middleware('permission:pbphh.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:pbphh.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:pbphh.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:pbphh.import')->name('import');
        Route::get('template', 'template')->middleware('permission:pbphh.create')->name('template');
    });

    // === REALISASI PNBP ===
    Route::controller(RealisasiPnbpController::class)->prefix('realisasi-pnbp')->name('realisasi-pnbp.')->group(function () {
        Route::post('{realisasi_pnbp}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:realisasi-pnbp.export')->name('export');
        Route::post('import-preview', 'previewImport')->middleware('permission:realisasi-pnbp.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:realisasi-pnbp.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:realisasi-pnbp.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:realisasi-pnbp.import')->name('import');
        Route::get('template', 'template')->middleware('permission:realisasi-pnbp.create')->name('template');
    });

    // === SKPS ===
    Route::controller(SkpsController::class)->prefix('skps')->name('skps.')->group(function () {
        Route::post('{skp}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:skps.export')->name('export');
        Route::post('import-preview', 'previewImport')->middleware('permission:skps.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:skps.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:skps.import')->name('commit-import');

        Route::post('import', 'import')->middleware('permission:skps.import')->name('import');
        Route::get('template', 'template')->middleware('permission:skps.create')->name('template');
    });

    // === KUPS ===
    Route::controller(KupsController::class)->prefix('kups')->name('kups.')->group(function () {
        Route::post('{kup}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:kups.export')->name('export');
        
        // Staging Import Routes
        Route::post('import-preview', 'previewImport')->middleware('permission:kups.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:kups.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:kups.import')->name('commit-import');
        
        Route::post('import', 'import')->middleware('permission:kups.import')->name('import'); // legacy if needed
        Route::get('template', 'template')->middleware('permission:kups.create')->name('template');
    });

    // === NILAI EKONOMI ===
    Route::controller(NilaiEkonomiController::class)->prefix('nilai-ekonomi')->name('nilai-ekonomi.')->group(function () {
        Route::post('{nilai_ekonomi}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:nilai-ekonomi.export')->name('export');
        Route::post('import-preview', 'previewImport')->middleware('permission:nilai-ekonomi.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:nilai-ekonomi.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:nilai-ekonomi.import')->name('commit-import');
        Route::post('import', 'import')->middleware('permission:nilai-ekonomi.import')->name('import');
        Route::get('template', 'template')->middleware('permission:nilai-ekonomi.create')->name('template');
    });

    // === PERKEMBANGAN KTH ===
    Route::controller(PerkembanganKthController::class)->prefix('perkembangan-kth')->name('perkembangan-kth.')->group(function () {
        Route::post('{perkembangan_kth}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:perkembangan-kth.export')->name('export');
        Route::post('import-preview', 'previewImport')->middleware('permission:perkembangan-kth.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:perkembangan-kth.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:perkembangan-kth.import')->name('commit-import');
        Route::post('import', 'import')->middleware('permission:perkembangan-kth.import')->name('import');
        Route::get('template', 'template')->middleware('permission:perkembangan-kth.create')->name('template');
    });

    // === NILAI TRANSAKSI EKONOMI ===
    Route::controller(NilaiTransaksiEkonomiController::class)->prefix('nilai-transaksi-ekonomi')->name('nilai-transaksi-ekonomi.')->group(function () {
        Route::post('{nilai_transaksi_ekonomi}/single-workflow-action', 'singleWorkflowAction')->name('single-workflow-action');
        Route::post('bulk-workflow-action', 'bulkWorkflowAction')->name('bulk-workflow-action');
        Route::get('export', 'export')->middleware('permission:nilai-transaksi-ekonomi.export')->name('export');
        Route::post('import-preview', 'previewImport')->middleware('permission:nilai-transaksi-ekonomi.import')->name('preview-import');
        Route::get('import-preview/{batch}', 'showPreview')->middleware('permission:nilai-transaksi-ekonomi.import')->name('show-preview');
        Route::post('import-commit/{batch}', 'commitImport')->middleware('permission:nilai-transaksi-ekonomi.import')->name('commit-import');
        Route::post('import', 'import')->middleware('permission:nilai-transaksi-ekonomi.import')->name('import');
        Route::get('template', 'template')->middleware('permission:nilai-transaksi-ekonomi.create')->name('template');
    });

    // === USER MANAGEMENT ===
    Route::get('users/export', [UserController::class, 'export'])->middleware('permission:users.export')->name('users.export');
    Route::get('users/template', [UserController::class, 'exportTemplate'])->middleware('permission:users.create')->name('users.template');
    Route::post('users/import', [UserController::class, 'import'])->middleware('permission:users.create')->name('users.import');

    // RESOURCES (Grouped by Permissions)
    $groupedResources = [
        'rehab' => [
            ['rehab-lahan', RehabLahanController::class, null],
            ['rehab-manggrove', RehabManggroveController::class, null],
            ['rhl-teknis', RhlTeknisController::class, ['rhl-teknis' => 'rhl_teknis']],
            ['reboisasi-ps', ReboisasiPsController::class, ['reboisasi-ps' => 'reboisasi_ps']],
        ],
        'penghijauan' => [
            ['penghijauan-lingkungan', PenghijauanLingkunganController::class, null],
        ],
        'perlindungan' => [
            ['kebakaran-hutan', KebakaranHutanController::class, ['kebakaran-hutan' => 'kebakaran_hutan']],
        ],
        'bina-usaha' => [
            ['pengunjung-wisata', PengunjungWisataController::class, ['pengunjung-wisata' => 'pengunjung_wisata']],
            ['pbphh', PbphhController::class, null],
            ['realisasi-pnbp', RealisasiPnbpController::class, ['realisasi-pnbp' => 'realisasi_pnbp']],
        ],
        'pemberdayaan' => [
            ['skps', SkpsController::class, null],
            ['kups', KupsController::class, null],
            ['nilai-ekonomi', NilaiEkonomiController::class, null],
            ['perkembangan-kth', PerkembanganKthController::class, ['perkembangan-kth' => 'perkembangan_kth']],
            ['nilai-transaksi-ekonomi', NilaiTransaksiEkonomiController::class, ['nilai-transaksi-ekonomi' => 'nilai_transaksi_ekonomi']],
        ],
        'users' => [
            ['users', UserController::class, null],
        ],
        'master' => [
            ['provinces', ProvinceController::class, null],
            ['regencies', RegencyController::class, null],
            ['districts', DistrictController::class, null],
            ['villages', VillageController::class, null],
            ['bangunan-kta', BangunanKtaController::class, null],
            ['sumber-dana', SumberDanaController::class, null],
            ['commodities', CommodityController::class, null],
            ['bukan-kayu', BukanKayuController::class, null],
            ['kayu', KayuController::class, null],
            ['jenis-produksi', JenisProduksiController::class, null],
            ['pengelola-wisata', PengelolaWisataController::class, null],
            ['pengelola-ps', PengelolaPsController::class, null],
            ['skema-perhutanan-sosial', SkemaPerhutananSosialController::class, null],
        ],
    ];

    foreach ($groupedResources as $perm => $resources) {
        foreach ($resources as $res) {
            $uri = $res[0];
            $ctrl = $res[1];
            $params = $res[2];
            
            $options = $params ? ['parameters' => $params] : [];
            
            Route::resource($uri, $ctrl, $options)->only(['create', 'store'])->middleware("permission:{$uri}.create");
            Route::resource($uri, $ctrl, $options)->only(['index', 'show'])->middleware("permission:{$uri}.view");
            Route::resource($uri, $ctrl, $options)->only(['edit', 'update'])->middleware("permission:{$uri}.edit");
            Route::resource($uri, $ctrl, $options)->only(['destroy'])->middleware("permission:{$uri}.delete");
        }
    }
    Route::resource('cdks', CdkController::class)->except(['create', 'edit', 'show']);

    
    Route::resource('hasil-hutan-kayu', HasilHutanKayuController::class, ['parameters' => ['hasil-hutan-kayu' => 'hasil_hutan_kayu']]);
    Route::resource('hasil-hutan-bukan-kayu', HasilHutanBukanKayuController::class, ['parameters' => ['hasil-hutan-bukan-kayu' => 'hasil_hutan_bukan_kayu']]);

    // === KEPEGAWAIAN ===
    Route::get('demografi-pegawai/export', [DemografiPegawaiController::class, 'export'])->middleware('permission:demografi-pegawai.export')->name('demografi-pegawai.export');
    Route::get('demografi-pegawai/template', [DemografiPegawaiController::class, 'template'])->middleware('permission:demografi-pegawai.create')->name('demografi-pegawai.template');
    Route::post('demografi-pegawai/import', [DemografiPegawaiController::class, 'import'])->middleware('permission:demografi-pegawai.create')->name('demografi-pegawai.import');
    Route::post('demografi-pegawai/bulk-delete', [DemografiPegawaiController::class, 'bulkDelete'])->middleware('permission:demografi-pegawai.delete')->name('demografi-pegawai.bulk-delete');
    Route::post('demografi-pegawai/bulk-restore', [DemografiPegawaiController::class, 'bulkRestore'])->middleware('permission:demografi-pegawai.edit')->name('demografi-pegawai.bulk-restore');
    Route::post('demografi-pegawai/{id}/restore', [DemografiPegawaiController::class, 'restore'])->middleware('permission:demografi-pegawai.edit')->name('demografi-pegawai.restore');
    
    Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['index', 'show'])->middleware('permission:demografi-pegawai.view');
    Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['create', 'store'])->middleware('permission:demografi-pegawai.create');
    Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['edit', 'update'])->middleware('permission:demografi-pegawai.edit');
    Route::resource('demografi-pegawai', DemografiPegawaiController::class)->only(['destroy'])->middleware('permission:demografi-pegawai.delete');

    Route::post('demografi-pegawai/{pegawai}/riwayat-kgb', [DemografiPegawaiController::class, 'storeKgb'])->middleware('permission:demografi-pegawai.create')->name('demografi-pegawai.kgb.store');
    Route::put('demografi-pegawai/riwayat-kgb/{riwayat_kgb}', [DemografiPegawaiController::class, 'updateKgb'])->middleware('permission:demografi-pegawai.edit')->name('demografi-pegawai.kgb.update');
    Route::delete('demografi-pegawai/riwayat-kgb/{riwayat_kgb}', [DemografiPegawaiController::class, 'destroyKgb'])->middleware('permission:demografi-pegawai.delete')->name('demografi-pegawai.kgb.destroy');

    Route::post('bezetting-jabatan/{bezetting_jabatan}/single-workflow-action', [BezettingJabatanController::class, 'singleWorkflowAction'])->name('bezetting-jabatan.single-workflow-action');
    Route::post('bezetting-jabatan/bulk-workflow-action', [BezettingJabatanController::class, 'bulkWorkflowAction'])->name('bezetting-jabatan.bulk-workflow-action');
    
    Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['index', 'show'])->middleware('permission:bezetting-jabatan.view');
    Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['create', 'store'])->middleware('permission:bezetting-jabatan.create');
    Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['edit', 'update'])->middleware('permission:bezetting-jabatan.edit');
    Route::resource('bezetting-jabatan', BezettingJabatanController::class, ['parameters' => ['bezetting-jabatan' => 'bezetting_jabatan']])->only(['destroy'])->middleware('permission:bezetting-jabatan.delete');

    Route::get('proyeksi-gaji/export', [ProyeksiGajiController::class, 'export'])->middleware('permission:proyeksi-gaji.export')->name('proyeksi-gaji.export');
    Route::get('proyeksi-gaji', [ProyeksiGajiController::class, 'index'])->middleware('permission:proyeksi-gaji.view')->name('proyeksi-gaji.index');

    // Impersonation Routes
    Route::impersonate();


    // Activity Log
    Route::resource('activity-log', ActivityLogController::class)->only(['index']);

    // Master Data
    Route::resource('provinces', ProvinceController::class);
    Route::resource('regencies', RegencyController::class);
    Route::resource('districts', DistrictController::class);
    Route::resource('villages', VillageController::class);
    Route::resource('bangunan-kta', BangunanKtaController::class);
    Route::resource('sumber-dana', SumberDanaController::class);
    Route::resource('commodities', CommodityController::class);
    Route::resource('bukan-kayu', BukanKayuController::class);
    Route::resource('kayu', KayuController::class);
    Route::resource('jenis-produksi', JenisProduksiController::class);
    Route::resource('pengelola-wisata', PengelolaWisataController::class);
    Route::resource('pengelola-ps', PengelolaPsController::class);
    Route::resource('skema-perhutanan-sosial', SkemaPerhutananSosialController::class);

    // Kepegawaian
    Route::get('demografi-pegawai/export', [DemografiPegawaiController::class, 'export'])->name('demografi-pegawai.export');
    Route::get('demografi-pegawai/template', [DemografiPegawaiController::class, 'template'])->name('demografi-pegawai.template');
    Route::post('demografi-pegawai/import', [DemografiPegawaiController::class, 'import'])->name('demografi-pegawai.import');
    Route::post('demografi-pegawai/bulk-delete', [DemografiPegawaiController::class, 'bulkDelete'])->name('demografi-pegawai.bulk-delete');
    Route::post('demografi-pegawai/bulk-restore', [DemografiPegawaiController::class, 'bulkRestore'])->name('demografi-pegawai.bulk-restore');
    Route::post('demografi-pegawai/{id}/restore', [DemografiPegawaiController::class, 'restore'])->name('demografi-pegawai.restore');
    Route::resource('demografi-pegawai', DemografiPegawaiController::class);
    Route::post('demografi-pegawai/{pegawai}/riwayat-kgb', [DemografiPegawaiController::class, 'storeKgb'])->name('demografi-pegawai.kgb.store');
    Route::put('demografi-pegawai/riwayat-kgb/{riwayat_kgb}', [DemografiPegawaiController::class, 'updateKgb'])->name('demografi-pegawai.kgb.update');
    Route::delete('demografi-pegawai/riwayat-kgb/{riwayat_kgb}', [DemografiPegawaiController::class, 'destroyKgb'])->name('demografi-pegawai.kgb.destroy');
    Route::post('bezetting-jabatan/{bezetting_jabatan}/single-workflow-action', [BezettingJabatanController::class, 'singleWorkflowAction'])->name('bezetting-jabatan.single-workflow-action');
    Route::post('bezetting-jabatan/bulk-workflow-action', [BezettingJabatanController::class, 'bulkWorkflowAction'])->name('bezetting-jabatan.bulk-workflow-action');
    Route::resource('bezetting-jabatan', BezettingJabatanController::class)->parameters(['bezetting-jabatan' => 'bezetting_jabatan']);
    Route::get('proyeksi-gaji/export', [ProyeksiGajiController::class, 'export'])->name('proyeksi-gaji.export');
    Route::get('proyeksi-gaji', [ProyeksiGajiController::class, 'index'])->name('proyeksi-gaji.index');

    // Rekap Kepegawaian Bulanan
    Route::prefix('rekap-bulanan')->name('rekap-bulanan.')->group(function () {
        Route::get('/', [RekapBulananController::class, 'index'])->name('index');
        Route::post('/generate', [RekapBulananController::class, 'generate'])->name('generate');
        Route::get('/export/{year}/{month}', [RekapBulananController::class, 'export'])->name('export');
        Route::get('/export-bezetting/{year}/{month}', [RekapBulananController::class, 'exportBezetting'])->name('export-bezetting');
        Route::get('/{year}/{month}', [RekapBulananController::class, 'show'])->name('show');
        Route::get('/{year}/{month}/pegawai', [RekapBulananController::class, 'showPegawai'])->name('show-pegawai');

        // Workflow Actions
        Route::post('/{id}/single-workflow-action', [RekapBulananController::class, 'singleWorkflowAction'])->name('single-workflow-action');
        Route::post('/bulk-workflow-action', [RekapBulananController::class, 'bulkWorkflowAction'])->name('bulk-workflow-action');
        Route::delete('/{id}', [RekapBulananController::class, 'destroy'])->name('destroy');
    });

    // Backup Management
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download')->where('filename', '.*');
    Route::delete('backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy')->where('filename', '.*');

    // Clear Cache
    // Route::get('/clear-cache', function () {
    //     Artisan::call('optimize:clear');
    //     return redirect()->back()->with('success', 'Cache cleared successfully!');
    // })->name('clear-cache');

    // Run Seeders
    // Route::get('/run-seeder/multi-cdk-test-user', function () {
    //     Artisan::call('db:seed', ['--class' => 'MultiCdkTestUserSeeder']);
    //     return redirect()->back()->with('success', 'MultiCdkTestUserSeeder executed successfully!');
    // })->name('run-seeder.multi-cdk-test-user');

    // Route::get('/run-seeder/cdk-user', function () {
    //     Artisan::call('db:seed', ['--class' => 'CdkUserSeeder']);
    //     return 'CdkUserSeeder executed successfully!';
    // })->name('run-seeder.cdk-user');

    // Run migration with safety check
    // Route::get('/run-migration-add-cdk-id', function (\Illuminate\Http\Request $request) {

    //     $migrationName = '2026_05_18_041136_add_cdk_id_to_all_data_tables';
    //     $migrationPath = 'database/migrations/2026_05_18_041136_add_cdk_id_to_all_data_tables.php';

    //     // 1. Check if 'cdks' table exists
    //     $cdksTableExists = \Illuminate\Support\Facades\Schema::hasTable('cdks');
        
    //     // 2. Check if migration has already been run
    //     $isAlreadyRun = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', $migrationName)->exists();

    //     // 3. Check status of target tables
    //     $tables = [
    //         'rehab_lahan',
    //         'penghijauan_lingkungan',
    //         'rehab_manggrove',
    //         'rhl_teknis',
    //         'reboisasi_ps',
    //         'kebakaran_hutan',
    //         'pengunjung_wisata',
    //         'hasil_hutan_kayu',
    //         'hasil_hutan_bukan_kayu',
    //         'pbphh',
    //         'realisasi_pnbp',
    //         'skps',
    //         'kups',
    //         'nilai_ekonomi',
    //         'nilai_transaksi_ekonomi',
    //         'perkembangan_kth',
    //         'pegawais',
    //         'bezettings',
    //         'rekap_bulanan_pegawai',
    //         'rekap_statistik_bulanan',
    //     ];

    //     $tableStatus = [];
    //     $existingCdkIdColumns = 0;
    //     $existingTablesCount = 0;

    //     foreach ($tables as $tableName) {
    //         $exists = \Illuminate\Support\Facades\Schema::hasTable($tableName);
    //         $hasColumn = $exists ? \Illuminate\Support\Facades\Schema::hasColumn($tableName, 'cdk_id') : false;
            
    //         if ($exists) {
    //             $existingTablesCount++;
    //         }
    //         if ($hasColumn) {
    //             $existingCdkIdColumns++;
    //         }

    //         $tableStatus[$tableName] = [
    //             'exists' => $exists,
    //             'has_cdk_id' => $hasColumn,
    //         ];
    //     }

    //     // Determine safety
    //     $isSafe = $cdksTableExists;
    //     $message = '';
        
    //     if (!$isSafe) {
    //         $message = "Unsafe: The 'cdks' table does not exist. You must run the cdks table creation migration first.";
    //     } elseif ($isAlreadyRun) {
    //         $message = "Safe (Already Run): This migration has already been marked as run in the 'migrations' table.";
    //     } else {
    //         $message = "Safe to run. " . ($existingTablesCount) . " out of " . count($tables) . " tables exist.";
    //     }

    //     $execute = $request->query('execute') === 'true';

    //     if ($execute && $isSafe && !$isAlreadyRun) {
    //         try {
    //             \Illuminate\Support\Facades\Artisan::call('migrate', [
    //                 '--path' => $migrationPath,
    //                 '--force' => true,
    //             ]);
    //             $output = \Illuminate\Support\Facades\Artisan::output();
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Migration ran successfully.',
    //                 'safety_check' => [
    //                     'safe' => $isSafe,
    //                     'message' => $message,
    //                     'cdks_table_exists' => $cdksTableExists,
    //                     'already_run' => $isAlreadyRun,
    //                     'tables_checked' => count($tables),
    //                     'tables_exist' => $existingTablesCount,
    //                     'columns_exist' => $existingCdkIdColumns,
    //                 ],
    //                 'artisan_output' => $output,
    //             ]);
    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Migration failed with an exception.',
    //                 'error' => $e->getMessage(),
    //             ], 500);
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'execute_triggered' => $execute,
    //         'safety_check' => [
    //             'safe' => $isSafe,
    //             'message' => $message,
    //             'cdks_table_exists' => $cdksTableExists,
    //             'already_run' => $isAlreadyRun,
    //             'tables_checked' => count($tables),
    //             'tables_exist' => $existingTablesCount,
    //             'columns_exist' => $existingCdkIdColumns,
    //             'table_details' => $tableStatus,
    //         ],
    //         'instruction' => $isSafe && !$isAlreadyRun 
    //             ? "To execute the migration, append '?execute=true' to this URL."
    //             : "Cannot execute (either unsafe or already run)."
    //     ]);
    // })->name('run-migration-add-cdk-id');
});


require __DIR__ . '/auth.php';
