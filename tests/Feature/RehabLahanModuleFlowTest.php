<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Models\RehabLahan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RehabLahanModuleFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'rhl_flow_testing', 'database.connections.rhl_flow_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('rhl_flow_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email');
            $table->string('password'); $table->unsignedBigInteger('cdk_id')->nullable(); $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id'); $table->unsignedBigInteger('role_id');
        });
        Schema::create('m_provinces', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('m_regencies', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('province_id'); $table->string('name');
        });
        Schema::create('m_districts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('regency_id'); $table->string('name');
        });
        Schema::create('m_villages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('district_id'); $table->string('name');
        });
        Schema::create('m_sumber_dana', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('rehab_lahan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('village_id');
            $table->string('coordinates')->nullable();
            $table->decimal('target_annual', 15, 2); $table->decimal('realization', 15, 2);
            $table->string('fund_source'); $table->string('status')->default('draft');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->string('rejection_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->unsignedBigInteger('user_id');
            $table->string('module_name'); $table->string('filename');
            $table->string('status'); $table->unsignedInteger('imported_count')->default(0);
            $table->text('error_message')->nullable(); $table->timestamps();
        });
        Schema::create('import_staging_rows', function (Blueprint $table) {
            $table->id(); $table->uuid('import_batch_id'); $table->integer('row_number');
            $table->json('data_payload'); $table->string('status');
            $table->text('validation_errors')->nullable(); $table->timestamps();
        });
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id(); $table->string('log_name')->nullable(); $table->text('description');
            $table->string('subject_type')->nullable(); $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable(); $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('event')->nullable(); $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable(); $table->timestamps();
        });

        DB::table('m_provinces')->insert(['id' => 35, 'name' => 'Jawa Timur']);
        DB::table('m_regencies')->insert(['id' => 1, 'province_id' => 35, 'name' => 'Kabupaten A']);
        DB::table('m_districts')->insert(['id' => 2, 'regency_id' => 1, 'name' => 'Kecamatan B']);
        DB::table('m_villages')->insert(['id' => 3, 'district_id' => 2, 'name' => 'Desa C']);
        DB::table('m_sumber_dana')->insert(['id' => 4, 'name' => 'apbd']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['rehab-lahan', 'penghijauan-lingkungan', 'rehab-manggrove', 'rhl-teknis', 'reboisasi-ps'] as $feature) {
            foreach (['view', 'create', 'edit', 'delete', 'approve', 'import', 'export'] as $action) {
                Permission::create(['name' => "{$feature}.{$action}", 'guard_name' => 'web']);
            }
        }
        Role::create(['name' => 'pelaksana', 'guard_name' => 'web']);
    }

    private function user(array $permissions): User
    {
        $user = User::create([
            'name' => 'Operator', 'email' => uniqid('operator-').'@example.test', 'password' => 'password',
        ]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function payload(): array
    {
        return [
            'year' => 2026, 'month' => 9, 'province_id' => 35,
            'regency_id' => 1, 'district_id' => 2, 'village_id' => 3,
            'target_annual' => 2, 'realization' => 1.5, 'fund_source' => 'APBD',
        ];
    }

    public function test_access_requires_authentication_and_permission(): void
    {
        $this->get(route('rehab-lahan.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user([]))->get(route('rehab-lahan.index'))
            ->assertRedirect()->assertSessionHas('error');
        $this->actingAs($this->user(['rehab-lahan.view']))
            ->get(route('rehab-lahan.index'))->assertOk();
    }

    public function test_create_update_and_delete_keep_the_existing_contract(): void
    {
        $this->actingAs($this->user(['rehab-lahan.create', 'rehab-lahan.edit', 'rehab-lahan.delete']));
        $this->post(route('rehab-lahan.store'), $this->payload())
            ->assertRedirect(route('rehab-lahan.index'));
        $rehab = RehabLahan::firstOrFail();
        $this->assertSame('draft', $rehab->status);

        $this->put(route('rehab-lahan.update', $rehab), [
            ...$this->payload(), 'realization' => 1.75,
        ])->assertRedirect(route('rehab-lahan.index'));
        $this->assertDatabaseHas('rehab_lahan', ['id' => $rehab->id, 'realization' => 1.75]);

        $this->delete(route('rehab-lahan.destroy', $rehab))
            ->assertRedirect(route('rehab-lahan.index'));
        $this->assertSoftDeleted('rehab_lahan', ['id' => $rehab->id]);
    }

    public function test_operator_can_submit_but_cannot_approve_workflow(): void
    {
        $user = $this->user(['rehab-lahan.edit']);
        $user->assignRole('pelaksana');
        $rehab = RehabLahan::create($this->payload());

        $this->actingAs($user)->post(route('rehab-lahan.single-workflow-action', $rehab), [
            'action' => 'submit',
        ])->assertRedirect();
        $this->assertDatabaseHas('rehab_lahan', ['id' => $rehab->id, 'status' => 'waiting_kasi']);

        $this->actingAs($user)->post(route('rehab-lahan.single-workflow-action', $rehab), [
            'action' => 'approve',
        ])->assertForbidden();
    }

    public function test_preview_creates_batch_and_commit_dispatches_queue_job(): void
    {
        Queue::fake();
        $this->actingAs($this->user(['rehab-lahan.import']));
        $csv = implode("\n", [
            'Tahun,Bulan (Angka),Nama Kabupaten,Nama Kecamatan,Nama Desa,Target Tahunan (Ha),Realisasi (Ha),Sumber Dana',
            '2026,9,Kabupaten A,Kecamatan B,Desa C,2,1.5,APBD',
        ]);
        $file = UploadedFile::fake()->createWithContent('rehab-lahan.csv', $csv);

        $this->post(route('rehab-lahan.preview-import'), ['file' => $file])->assertRedirect();
        $batch = ImportBatch::firstOrFail();
        $this->assertSame('rehab-lahan', $batch->module_name);
        $this->assertSame('valid', $batch->stagingRows()->firstOrFail()->status);

        $this->get(route('rehab-lahan.show-preview', $batch))->assertOk();
        $this->post(route('rehab-lahan.commit-import', $batch))->assertRedirect();
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'processing']);
        Queue::assertPushed(ProcessImportBatch::class, fn ($job) => $job->batchId === $batch->id);
    }

    public function test_export_keeps_excel_download_route(): void
    {
        $this->actingAs($this->user(['rehab-lahan.export']));
        RehabLahan::create([...$this->payload(), 'status' => 'final']);

        $response = $this->get(route('rehab-lahan.export', ['year' => 2026]));
        $response->assertOk();
        $this->assertStringContainsString('rehab-lahan-', $response->headers->get('content-disposition'));

        $dashboardExport = $this->get(route('dashboard.export-rehab-lahan', ['year' => 2026]));
        $dashboardExport->assertOk();
        $this->assertStringContainsString('laporan-rehabilitasi-lahan-', $dashboardExport->headers->get('content-disposition'));
    }

    public function test_direct_import_keeps_the_existing_excel_contract(): void
    {
        $this->actingAs($this->user(['rehab-lahan.import']));
        $csv = implode("\n", [
            'Tahun,Bulan (Angka),Nama Kabupaten,Nama Kecamatan,Nama Desa,Target Tahunan (Ha),Realisasi (Ha),Sumber Dana',
            '2026,9,Kabupaten A,Kecamatan B,Desa C,2,1.5,APBD',
        ]);

        $this->post(route('rehab-lahan.import'), [
            'file' => UploadedFile::fake()->createWithContent('rehab-lahan.csv', $csv),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('rehab_lahan', [
            'year' => 2026, 'month' => 9, 'target_annual' => 2,
            'realization' => 1.5, 'fund_source' => 'apbd',
        ]);
    }

    public function test_all_rhl_templates_are_downloadable_from_module_controllers(): void
    {
        $features = [
            'rehab-lahan' => 'template_import_rehab_lahan.xlsx',
            'penghijauan-lingkungan' => 'template_import_penghijauan_lingkungan.xlsx',
            'rehab-manggrove' => 'template_import_rehab_manggrove.xlsx',
            'rhl-teknis' => 'template_import_rhl_teknis.xlsx',
            'reboisasi-ps' => 'template_import_reboisasi_ps.xlsx',
        ];
        $permissions = array_map(fn ($feature) => "{$feature}.create", array_keys($features));
        $this->actingAs($this->user($permissions));

        foreach ($features as $feature => $filename) {
            $response = $this->get(route("{$feature}.template"));
            $response->assertOk();
            $this->assertStringContainsString($filename, $response->headers->get('content-disposition'));
        }
    }
}
