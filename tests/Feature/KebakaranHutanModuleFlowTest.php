<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Models\KebakaranHutan;
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

class KebakaranHutanModuleFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'kebakaran_flow_testing', 'database.connections.kebakaran_flow_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('kebakaran_flow_testing');

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
        Schema::create('m_pengelola_wisata', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('kebakaran_hutan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('village_id');
            $table->string('coordinates')->nullable(); $table->unsignedBigInteger('id_pengelola_wisata');
            $table->string('area_function'); $table->integer('number_of_fires');
            $table->string('fire_area'); $table->string('status')->default('draft');
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
        DB::table('m_pengelola_wisata')->insert(['id' => 4, 'name' => 'Pengelola D']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view', 'create', 'edit', 'delete', 'approve', 'import', 'export'] as $action) {
            Permission::create(['name' => "kebakaran-hutan.{$action}", 'guard_name' => 'web']);
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
            'id_pengelola_wisata' => 4, 'area_function' => 'Lindung',
            'number_of_fires' => 2, 'fire_area' => '1.5',
        ];
    }

    public function test_access_requires_authentication_and_permission(): void
    {
        $this->get(route('kebakaran-hutan.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user([]))->get(route('kebakaran-hutan.index'))
            ->assertRedirect()->assertSessionHas('error');
        $this->actingAs($this->user(['kebakaran-hutan.view']))
            ->get(route('kebakaran-hutan.index'))->assertOk();
    }

    public function test_create_update_and_delete_keep_the_existing_contract(): void
    {
        $this->actingAs($this->user(['kebakaran-hutan.create', 'kebakaran-hutan.edit', 'kebakaran-hutan.delete']));
        $this->post(route('kebakaran-hutan.store'), $this->payload())
            ->assertRedirect(route('kebakaran-hutan.index'));
        $fire = KebakaranHutan::firstOrFail();
        $this->assertSame('draft', $fire->status);

        $this->put(route('kebakaran-hutan.update', $fire), [
            ...$this->payload(), 'number_of_fires' => 3,
        ])->assertRedirect(route('kebakaran-hutan.index'));
        $this->assertDatabaseHas('kebakaran_hutan', ['id' => $fire->id, 'number_of_fires' => 3]);

        $this->delete(route('kebakaran-hutan.destroy', $fire))
            ->assertRedirect(route('kebakaran-hutan.index'));
        $this->assertSoftDeleted('kebakaran_hutan', ['id' => $fire->id]);
    }

    public function test_operator_can_submit_but_cannot_approve_workflow(): void
    {
        $user = $this->user(['kebakaran-hutan.edit']);
        $user->assignRole('pelaksana');
        $fire = KebakaranHutan::create($this->payload());

        $this->actingAs($user)->post(route('kebakaran-hutan.single-workflow-action', $fire), [
            'action' => 'submit',
        ])->assertRedirect();
        $this->assertDatabaseHas('kebakaran_hutan', ['id' => $fire->id, 'status' => 'waiting_kasi']);

        $this->actingAs($user)->post(route('kebakaran-hutan.single-workflow-action', $fire), [
            'action' => 'approve',
        ])->assertForbidden();
    }

    public function test_preview_creates_batch_and_commit_dispatches_queue_job(): void
    {
        Queue::fake();
        $this->actingAs($this->user(['kebakaran-hutan.import']));
        $csv = implode("\n", [
            'Tahun,Bulan (Angka 1-12),Nama Kabupaten/Kota,Nama Kecamatan,Nama Desa,Nama Pengelola Wisata,Fungsi Kawasan,Jumlah Kejadian,Luas Kebakaran (Ha)',
            '2026,9,Kabupaten A,Kecamatan B,Desa C,Pengelola D,Lindung,2,1.5',
        ]);
        $file = UploadedFile::fake()->createWithContent('kebakaran.csv', $csv);

        $this->post(route('kebakaran-hutan.preview-import'), ['file' => $file])->assertRedirect();
        $batch = ImportBatch::firstOrFail();
        $this->assertSame('kebakaran-hutan', $batch->module_name);
        $this->assertSame('valid', $batch->stagingRows()->firstOrFail()->status);

        $this->get(route('kebakaran-hutan.show-preview', $batch))->assertOk();
        $this->post(route('kebakaran-hutan.commit-import', $batch))->assertRedirect();
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'processing']);
        Queue::assertPushed(ProcessImportBatch::class, fn ($job) => $job->batchId === $batch->id);
    }

    public function test_export_keeps_excel_download_route(): void
    {
        $this->actingAs($this->user(['kebakaran-hutan.export']));
        KebakaranHutan::create([...$this->payload(), 'status' => 'final']);

        $response = $this->get(route('kebakaran-hutan.export', ['year' => 2026]));
        $response->assertOk();
        $this->assertStringContainsString('kebakaran-hutan-', $response->headers->get('content-disposition'));
    }
}
