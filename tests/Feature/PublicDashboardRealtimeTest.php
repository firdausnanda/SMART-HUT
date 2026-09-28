<?php

namespace Tests\Feature;

use App\Events\PublicDashboardChanged;
use App\Http\Controllers\DashboardController;
use App\Models\RehabLahan;
use App\Models\Pbphh;
use App\Models\RekapBulananPegawai;
use App\Models\RekapStatistikBulanan;
use App\Models\User;
use App\Services\PublicDashboardRealtime;
use App\Services\RekapKepegawaianService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicDashboardRealtimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'dashboard_testing',
            'database.connections.dashboard_testing' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
            'cache.default' => 'array',
            'activitylog.enabled' => false,
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        DB::purge('dashboard_testing');
        Cache::flush();
        require base_path('routes/channels.php');

        Schema::create('rehab_lahan', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('cdk_id');
            $table->string('status');
            $table->decimal('realization')->default(0);
            $table->decimal('target_annual')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('rekap_bulanan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->integer('periode_tahun');
            $table->integer('periode_bulan');
            $table->integer('cdk_id')->nullable();
            $table->integer('pegawai_id');
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('rekap_statistik_bulanan', function (Blueprint $table) {
            $table->id();
            $table->integer('periode_tahun');
            $table->integer('periode_bulan');
            $table->integer('cdk_id')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_only_committed_final_data_invalidates_relevant_public_dashboard_cache(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $year = (int) date('Y');
        $specific = "pembinaan_stats_v2_{$year}_1";
        $all = "pembinaan_stats_v2_{$year}_all";
        $yoy = "public_yoy_dashboard_stats_v3_{$year}_1";
        foreach ([$specific, $all, $yoy] as $key) {
            Cache::put($key, ['stale' => true], 300);
        }

        $record = RehabLahan::create([
            'year' => $year, 'month' => 1, 'cdk_id' => 1,
            'status' => 'draft', 'realization' => 5,
        ]);
        $this->assertTrue(Cache::has($specific));
        Event::assertNotDispatched(PublicDashboardChanged::class);

        DB::transaction(function () use ($record, $specific) {
            $record->update(['status' => 'final']);
            $this->assertTrue(Cache::has($specific));
            Event::assertNotDispatched(PublicDashboardChanged::class);
        });

        foreach ([$specific, $all, $yoy] as $key) {
            $this->assertFalse(Cache::has($key), $key);
        }
        Event::assertDispatched(PublicDashboardChanged::class, fn ($event) =>
            $event->domain === 'pembinaan'
            && $event->year === $year
            && $event->cdkId === 1
        );
    }

    public function test_year_and_cdk_correction_invalidates_both_scopes_but_rollback_does_not(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $record = RehabLahan::create([
            'year' => 2025, 'month' => 1, 'cdk_id' => 1,
            'status' => 'final', 'realization' => 5,
        ]);
        Event::fake([PublicDashboardChanged::class]);
        Cache::put('pembinaan_stats_v2_2025_1', 'old', 300);
        Cache::put('pembinaan_stats_v2_2026_2', 'new', 300);

        try {
            DB::transaction(function () use ($record) {
                $record->update(['year' => 2026, 'cdk_id' => 2]);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('rollback', $e->getMessage());
        }
        $this->assertTrue(Cache::has('pembinaan_stats_v2_2025_1'));
        $this->assertTrue(Cache::has('pembinaan_stats_v2_2026_2'));
        Event::assertNotDispatched(PublicDashboardChanged::class);

        $record->refresh();
        $record->update(['year' => 2026, 'cdk_id' => 2]);

        $this->assertFalse(Cache::has('pembinaan_stats_v2_2025_1'));
        $this->assertFalse(Cache::has('pembinaan_stats_v2_2026_2'));
        Event::assertDispatchedTimes(PublicDashboardChanged::class, 2);
    }

    public function test_final_deletion_invalidates_cache(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $record = RehabLahan::create([
            'year' => 2026, 'month' => 1, 'cdk_id' => 1,
            'status' => 'final', 'realization' => 5,
        ]);
        Event::fake([PublicDashboardChanged::class]);
        Cache::put('pembinaan_stats_v2_2026_1', 'old', 300);

        $record->delete();

        $this->assertFalse(Cache::has('pembinaan_stats_v2_2026_1'));
        Event::assertDispatchedTimes(PublicDashboardChanged::class, 1);
    }

    public function test_saving_final_parent_without_attribute_changes_refreshes_detail_based_statistics(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $record = RehabLahan::create([
            'year' => 2026, 'month' => 1, 'cdk_id' => 1,
            'status' => 'final', 'realization' => 5,
        ]);
        Event::fake([PublicDashboardChanged::class]);
        Cache::put('pembinaan_stats_v2_2026_1', 'old', 300);

        $record->save();

        $this->assertFalse(Cache::has('pembinaan_stats_v2_2026_1'));
    }

    public function test_restoring_final_data_invalidates_cache(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $record = RehabLahan::create([
            'year' => 2026, 'month' => 1, 'cdk_id' => 1,
            'status' => 'final', 'realization' => 5,
        ]);
        $record->delete();
        Event::fake([PublicDashboardChanged::class]);
        Cache::put('pembinaan_stats_v2_2026_1', 'stale', 300);

        $record->restore();

        $this->assertFalse(Cache::has('pembinaan_stats_v2_2026_1'));
        Event::assertDispatchedTimes(PublicDashboardChanged::class, 1);
    }

    public function test_yearless_final_data_invalidates_static_and_yearly_caches(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $year = (int) date('Y');
        Cache::put('pbphh_public_static_stats_v1_1', 'stale', 300);
        Cache::put("bina_usaha_stats_v3_{$year}_1", 'stale', 300);
        Cache::put("public_yoy_dashboard_stats_v3_{$year}_1", 'stale', 300);

        app(PublicDashboardRealtime::class)->recordChange(
            new Pbphh,
            null,
            ['status' => 'final', 'cdk_id' => 1],
        );

        $this->assertFalse(Cache::has('pbphh_public_static_stats_v1_1'));
        $this->assertFalse(Cache::has("bina_usaha_stats_v3_{$year}_1"));
        $this->assertFalse(Cache::has("public_yoy_dashboard_stats_v3_{$year}_1"));
        Event::assertDispatched(PublicDashboardChanged::class, fn ($event) =>
            $event->domain === 'bina_usaha'
            && $event->year === null
            && $event->cdkId === 1
            && $event->broadcastQueue() === 'dashboard-broadcasts'
            && count($event->broadcastOn()) === 2
        );
    }

    public function test_bulk_snapshot_deletion_invalidates_final_data_after_commit(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        RekapBulananPegawai::create([
            'periode_tahun' => 2026, 'periode_bulan' => 1,
            'pegawai_id' => 1, 'cdk_id' => 1, 'status' => 'final',
        ]);
        Event::fake([PublicDashboardChanged::class]);
        Cache::put('kepegawaian_stats_2026_1', 'stale', 300);

        DB::transaction(function () {
            app(RekapKepegawaianService::class)->deleteSnapshotsForPeriod(2026, 1, []);
            $this->assertTrue(Cache::has('kepegawaian_stats_2026_1'));
            Event::assertNotDispatched(PublicDashboardChanged::class);
        });

        $this->assertFalse(Cache::has('kepegawaian_stats_2026_1'));
        Event::assertDispatched(PublicDashboardChanged::class, fn ($event) =>
            $event->domain === 'kepegawaian' && $event->year === 2026 && $event->cdkId === 1
        );
    }

    public function test_empty_snapshot_recalculation_removes_final_summary(): void
    {
        Event::fake([PublicDashboardChanged::class]);
        $summary = RekapStatistikBulanan::create([
            'periode_tahun' => 2026, 'periode_bulan' => 1,
            'cdk_id' => 1, 'status' => 'final',
        ]);
        Event::fake([PublicDashboardChanged::class]);
        Cache::put('kepegawaian_stats_2026_1', 'stale', 300);

        app(RekapKepegawaianService::class)->recalculateStatistik(2026, 1);

        $this->assertSoftDeleted($summary);
        $this->assertFalse(Cache::has('kepegawaian_stats_2026_1'));
    }

    public function test_cdk_user_cannot_subscribe_to_another_cdk_or_province_channel(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\HandleInertiaRequests::class);
        $user = new User(['cdk_id' => 1]);
        $user->id = 7;
        $this->actingAs($user);
        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-dashboard.cdk.1', 'socket_id' => '123.456',
        ])->assertOk();
        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-dashboard.cdk.2', 'socket_id' => '123.456',
        ])->assertForbidden();
        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-dashboard.province', 'socket_id' => '123.456',
        ])->assertForbidden();
    }

    public function test_public_dashboard_scope_uses_authenticated_users_cdk(): void
    {
        $user = new User(['cdk_id' => 1]);
        $user->id = 7;
        $this->actingAs($user);

        $resolve = new \ReflectionMethod(DashboardController::class, 'resolvePublicCdkId');
        $this->assertSame(1, $resolve->invoke(new DashboardController, 2));
        $this->assertSame(1, $resolve->invoke(new DashboardController, null));
    }
}
