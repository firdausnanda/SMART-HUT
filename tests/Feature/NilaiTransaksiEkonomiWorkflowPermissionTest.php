<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NilaiTransaksiEkonomiWorkflowPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'nte_workflow_testing', 'database.connections.nte_workflow_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('nte_workflow_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->timestamps();
        });
        Schema::create('cdks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('nilai_transaksi_ekonomi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->string('status');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->string('rejection_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('bezettings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->string('nama_jabatan');
            $table->unsignedInteger('kebutuhan')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->string('rejection_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('rekap_statistik_bulanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('periode_tahun');
            $table->integer('periode_bulan');
            $table->string('status')->default('draft');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->string('rejection_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['hasil_hutan_kayu', 'hasil_hutan_bukan_kayu'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cdk_id')->nullable();
                $table->string('forest_type');
                $table->integer('year');
                $table->string('status');
                $table->timestamp('approved_by_kasi_at')->nullable();
                $table->timestamp('approved_by_cdk_at')->nullable();
                $table->string('rejection_note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('cdks')->insert(['id' => 42, 'nama' => 'CDK 42']);
        foreach (array_merge([
            'nilai-transaksi-ekonomi.edit', 'bezetting-jabatan.create', 'bezetting-jabatan.edit',
            'demografi-pegawai.view', 'produksi-hutan-negara.create', 'produksi-hutan-negara.import',
        ], self::approvalPermissions()) as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
        Role::create(['name' => 'kasi', 'guard_name' => 'web']);
        Role::create(['name' => 'pelaksana', 'guard_name' => 'web']);
    }

    public function test_kasi_with_approve_only_can_approve_and_reject_reports(): void
    {
        $kasi = User::create([
            'name' => 'Kasi', 'email' => 'kasi@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $kasi->assignRole('kasi');
        $kasi->givePermissionTo('nilai-transaksi-ekonomi.approve');
        $this->assertFalse($kasi->can('nilai-transaksi-ekonomi.edit'));

        DB::table('nilai_transaksi_ekonomi')->insert([
            ['id' => 1, 'cdk_id' => 42, 'status' => 'waiting_kasi'],
            ['id' => 2, 'cdk_id' => 42, 'status' => 'waiting_kasi'],
            ['id' => 3, 'cdk_id' => 42, 'status' => 'waiting_kasi'],
            ['id' => 4, 'cdk_id' => 42, 'status' => 'waiting_kasi'],
        ]);

        $this->actingAs($kasi)->from('/nilai-transaksi-ekonomi')
            ->post(route('nilai-transaksi-ekonomi.single-workflow-action', 1), ['action' => 'approve'])
            ->assertRedirect('/nilai-transaksi-ekonomi')
            ->assertSessionHas('success');
        $this->assertDatabaseHas('nilai_transaksi_ekonomi', ['id' => 1, 'status' => 'waiting_cdk']);

        $this->from('/nilai-transaksi-ekonomi')
            ->post(route('nilai-transaksi-ekonomi.bulk-workflow-action'), [
                'ids' => [2, 3], 'action' => 'approve',
            ])
            ->assertRedirect('/nilai-transaksi-ekonomi')
            ->assertSessionHas('success');
        $this->assertDatabaseHas('nilai_transaksi_ekonomi', ['id' => 2, 'status' => 'waiting_cdk']);
        $this->assertDatabaseHas('nilai_transaksi_ekonomi', ['id' => 3, 'status' => 'waiting_cdk']);

        $this->from('/nilai-transaksi-ekonomi')
            ->post(route('nilai-transaksi-ekonomi.single-workflow-action', 4), [
                'action' => 'reject', 'rejection_note' => 'Periksa kembali data',
            ])
            ->assertRedirect('/nilai-transaksi-ekonomi')
            ->assertSessionHas('success');
        $this->assertDatabaseHas('nilai_transaksi_ekonomi', [
            'id' => 4, 'status' => 'rejected', 'rejection_note' => 'Periksa kembali data',
        ]);
    }

    public function test_approval_permission_does_not_grant_submission(): void
    {
        $kasi = User::create([
            'name' => 'Kasi', 'email' => 'kasi@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $kasi->assignRole('kasi');
        $kasi->givePermissionTo('nilai-transaksi-ekonomi.approve');
        DB::table('nilai_transaksi_ekonomi')->insert(['id' => 1, 'cdk_id' => 42, 'status' => 'draft']);

        $this->actingAs($kasi)->post(route('nilai-transaksi-ekonomi.single-workflow-action', 1), [
            'action' => 'submit',
        ])->assertForbidden();
        $this->assertDatabaseHas('nilai_transaksi_ekonomi', ['id' => 1, 'status' => 'draft']);
    }

    public function test_kasi_with_approval_permissions_can_reach_workflow_actions_in_other_modules(): void
    {
        $kasi = User::create([
            'name' => 'Kasi', 'email' => 'kasi@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $kasi->assignRole('kasi');
        $kasi->givePermissionTo(self::approvalPermissions());
        $this->actingAs($kasi);

        foreach (self::otherWorkflowModules() as $module) {
            $this->postJson(route("{$module}.bulk-workflow-action"), [])
                ->assertUnprocessable();
        }
    }

    public function test_bezetting_uses_granular_permissions_for_approval_and_creation(): void
    {
        $kasi = User::create([
            'name' => 'Kasi', 'email' => 'kasi@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $kasi->assignRole('kasi');
        $kasi->givePermissionTo('bezetting-jabatan.approve');
        DB::table('bezettings')->insert([
            'id' => 1, 'cdk_id' => 42, 'nama_jabatan' => 'Kepala Seksi', 'status' => 'waiting_kasi',
        ]);

        $this->actingAs($kasi)->from('/bezetting-jabatan')
            ->post(route('bezetting-jabatan.single-workflow-action', 1), ['action' => 'approve'])
            ->assertRedirect('/bezetting-jabatan')->assertSessionHas('success');
        $this->assertDatabaseHas('bezettings', ['id' => 1, 'status' => 'waiting_cdk']);

        $editor = User::create([
            'name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $editor->givePermissionTo('bezetting-jabatan.create');
        $this->actingAs($editor)->from('/bezetting-jabatan')
            ->post(route('bezetting-jabatan.store'), ['nama_jabatan' => 'Analis', 'kebutuhan' => 2])
            ->assertRedirect('/bezetting-jabatan')->assertSessionHas('success');
        $this->assertDatabaseHas('bezettings', ['nama_jabatan' => 'Analis', 'cdk_id' => 42]);
    }

    public function test_rekap_uses_demografi_permissions_and_checks_each_workflow_action(): void
    {
        $kasi = User::create([
            'name' => 'Kasi', 'email' => 'kasi@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $kasi->assignRole('kasi');
        $kasi->givePermissionTo(['demografi-pegawai.view', 'demografi-pegawai.approve']);
        DB::table('rekap_statistik_bulanan')->insert([
            ['id' => 1, 'cdk_id' => 42, 'periode_tahun' => 2026, 'periode_bulan' => 1, 'status' => 'waiting_kasi'],
            ['id' => 2, 'cdk_id' => 42, 'periode_tahun' => 2026, 'periode_bulan' => 2, 'status' => 'draft'],
            ['id' => 3, 'cdk_id' => 42, 'periode_tahun' => 2026, 'periode_bulan' => 3, 'status' => 'waiting_kasi'],
        ]);

        $this->actingAs($kasi)->get(route('rekap-bulanan.index', ['year' => 2026]))->assertOk();
        $this->from('/rekap-bulanan')
            ->post(route('rekap-bulanan.single-workflow-action', 1), [
                'model_type' => 'statistik', 'action' => 'approve',
            ])->assertRedirect('/rekap-bulanan')->assertSessionHas('success');
        $this->assertDatabaseHas('rekap_statistik_bulanan', ['id' => 1, 'status' => 'waiting_cdk']);

        $this->post(route('rekap-bulanan.single-workflow-action', 2), [
            'model_type' => 'statistik', 'action' => 'submit',
        ])->assertForbidden();
        $this->assertDatabaseHas('rekap_statistik_bulanan', ['id' => 2, 'status' => 'draft']);

        $this->postJson(route('rekap-bulanan.bulk-workflow-action'), [
            'ids' => [3], 'model_type' => 'statistik', 'action' => 'submit',
        ])->assertForbidden();
        $this->assertDatabaseHas('rekap_statistik_bulanan', ['id' => 3, 'status' => 'waiting_kasi']);
    }

    public function test_forest_bulk_approval_requires_permission_for_every_selected_type(): void
    {
        $kasi = User::create([
            'name' => 'Kasi', 'email' => 'kasi@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $kasi->assignRole('kasi');
        $kasi->givePermissionTo('produksi-hutan-negara.approve');
        $this->actingAs($kasi);

        foreach (['hasil_hutan_kayu' => 'hasil-hutan-kayu', 'hasil_hutan_bukan_kayu' => 'hasil-hutan-bukan-kayu'] as $tableName => $routePrefix) {
            DB::table($tableName)->insert([
                ['id' => 1, 'cdk_id' => 42, 'forest_type' => 'Hutan Negara', 'year' => 2026, 'status' => 'waiting_kasi'],
                ['id' => 2, 'cdk_id' => 42, 'forest_type' => 'Hutan Rakyat', 'year' => 2026, 'status' => 'waiting_kasi'],
            ]);

            $this->postJson(route("{$routePrefix}.bulk-workflow-action"), [
                'ids' => [1, 2], 'action' => 'approve',
            ])->assertForbidden();
            $this->assertDatabaseHas($tableName, ['id' => 1, 'status' => 'waiting_kasi']);
            $this->assertDatabaseHas($tableName, ['id' => 2, 'status' => 'waiting_kasi']);

            $this->from('/' . $routePrefix)
                ->post(route("{$routePrefix}.bulk-workflow-action"), [
                    'ids' => [1], 'action' => 'approve',
                ])->assertRedirect('/' . $routePrefix)->assertSessionHas('success');
            $this->assertDatabaseHas($tableName, ['id' => 1, 'status' => 'waiting_cdk']);
        }
    }

    public function test_forest_create_and_import_routes_accept_category_permissions(): void
    {
        $user = User::create([
            'name' => 'Operator', 'email' => 'operator@example.test', 'password' => 'password', 'cdk_id' => 42,
        ]);
        $user->givePermissionTo(['produksi-hutan-negara.create', 'produksi-hutan-negara.import']);
        $this->actingAs($user);

        foreach (['hasil-hutan-kayu', 'hasil-hutan-bukan-kayu'] as $module) {
            $this->postJson(route("{$module}.store"), ['forest_type' => 'Hutan Negara'])
                ->assertUnprocessable();
            $this->postJson(route("{$module}.preview-import"), ['forest_type' => 'Hutan Negara'])
                ->assertUnprocessable();
        }
    }

    private static function otherWorkflowModules(): array
    {
        return [
            'rehab-lahan', 'penghijauan-lingkungan', 'rehab-manggrove', 'rhl-teknis', 'reboisasi-ps',
            'kebakaran-hutan', 'pengunjung-wisata',
            'skps', 'kups', 'nilai-ekonomi', 'perkembangan-kth',
            'pbphh', 'realisasi-pnbp',
            'hasil-hutan-kayu', 'hasil-hutan-bukan-kayu',
            'bezetting-jabatan', 'rekap-bulanan',
        ];
    }

    private static function approvalPermissions(): array
    {
        return [
            'nilai-transaksi-ekonomi.approve',
            'rehab-lahan.approve', 'penghijauan-lingkungan.approve', 'rehab-manggrove.approve',
            'rhl-teknis.approve', 'reboisasi-ps.approve',
            'kebakaran-hutan.approve', 'pengunjung-wisata.approve',
            'skps.approve', 'kups.approve', 'nilai-ekonomi.approve', 'perkembangan-kth.approve',
            'pbphh.approve', 'realisasi-pnbp.approve',
            'produksi-hutan-negara.approve',
            'bezetting-jabatan.approve', 'demografi-pegawai.approve',
        ];
    }
}
