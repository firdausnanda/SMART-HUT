<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Models\PengunjungWisata;
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

class PengunjungWisataModuleFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'pengunjung_flow_testing', 'database.connections.pengunjung_flow_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('pengunjung_flow_testing');

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
        Schema::create('m_pengelola_wisata', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('pengunjung_wisata', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('id_pengelola_wisata');
            $table->integer('number_of_visitors'); $table->decimal('gross_income', 15, 2);
            $table->string('status')->default('draft');
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

        DB::table('m_pengelola_wisata')->insert(['id' => 4, 'name' => 'Pengelola D']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view', 'create', 'edit', 'delete', 'approve', 'import', 'export'] as $action) {
            Permission::create(['name' => "pengunjung-wisata.{$action}", 'guard_name' => 'web']);
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
            'year' => 2026, 'month' => 9, 'id_pengelola_wisata' => 4,
            'number_of_visitors' => 12, 'gross_income' => 150000,
        ];
    }

    public function test_access_requires_authentication_and_permission(): void
    {
        $this->get(route('pengunjung-wisata.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user([]))->get(route('pengunjung-wisata.index'))
            ->assertRedirect()->assertSessionHas('error');
        $this->actingAs($this->user(['pengunjung-wisata.view']))
            ->get(route('pengunjung-wisata.index'))->assertOk();
    }

    public function test_create_update_and_delete_keep_the_existing_contract(): void
    {
        $this->actingAs($this->user(['pengunjung-wisata.create', 'pengunjung-wisata.edit', 'pengunjung-wisata.delete']));
        $this->post(route('pengunjung-wisata.store'), $this->payload())
            ->assertRedirect(route('pengunjung-wisata.index'));
        $visitor = PengunjungWisata::firstOrFail();
        $this->assertSame('draft', $visitor->status);

        $this->put(route('pengunjung-wisata.update', $visitor), [
            ...$this->payload(), 'number_of_visitors' => 13,
        ])->assertRedirect(route('pengunjung-wisata.index'));
        $this->assertDatabaseHas('pengunjung_wisata', ['id' => $visitor->id, 'number_of_visitors' => 13]);

        $this->delete(route('pengunjung-wisata.destroy', $visitor))
            ->assertRedirect(route('pengunjung-wisata.index'));
        $this->assertSoftDeleted('pengunjung_wisata', ['id' => $visitor->id]);
    }

    public function test_operator_can_submit_but_cannot_approve_workflow(): void
    {
        $user = $this->user(['pengunjung-wisata.edit']);
        $user->assignRole('pelaksana');
        $visitor = PengunjungWisata::create($this->payload());

        $this->actingAs($user)->post(route('pengunjung-wisata.single-workflow-action', $visitor), [
            'action' => 'submit',
        ])->assertRedirect();
        $this->assertDatabaseHas('pengunjung_wisata', ['id' => $visitor->id, 'status' => 'waiting_kasi']);

        $this->actingAs($user)->post(route('pengunjung-wisata.single-workflow-action', $visitor), [
            'action' => 'approve',
        ])->assertForbidden();
    }

    public function test_preview_creates_batch_and_commit_dispatches_queue_job(): void
    {
        Queue::fake();
        $this->actingAs($this->user(['pengunjung-wisata.import']));
        $csv = implode("\n", [
            'Tahun,Bulan (Angka 1-12),Nama Pengelola Wisata,Jumlah Pengunjung,Pendapatan Bruto (Rp)',
            '2026,9,Pengelola D,12,150000',
        ]);
        $file = UploadedFile::fake()->createWithContent('pengunjung.csv', $csv);

        $this->post(route('pengunjung-wisata.preview-import'), ['file' => $file])->assertRedirect();
        $batch = ImportBatch::firstOrFail();
        $this->assertSame('pengunjung-wisata', $batch->module_name);
        $this->assertSame('valid', $batch->stagingRows()->firstOrFail()->status);

        $this->get(route('pengunjung-wisata.show-preview', $batch))->assertOk();
        $this->post(route('pengunjung-wisata.commit-import', $batch))->assertRedirect();
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'processing']);
        Queue::assertPushed(ProcessImportBatch::class, fn ($job) => $job->batchId === $batch->id);
    }

    public function test_export_keeps_excel_download_route(): void
    {
        $this->actingAs($this->user(['pengunjung-wisata.export']));
        PengunjungWisata::create([...$this->payload(), 'status' => 'final']);

        $response = $this->get(route('pengunjung-wisata.export', ['year' => 2026]));
        $response->assertOk();
        $this->assertStringContainsString('pengunjung-wisata-', $response->headers->get('content-disposition'));
    }

    public function test_direct_import_and_template_keep_the_excel_contract(): void
    {
        $this->actingAs($this->user(['pengunjung-wisata.import', 'pengunjung-wisata.create']));
        $csv = implode("\n", [
            'Tahun,Bulan (Angka 1-12),Nama Pengelola Wisata,Jumlah Pengunjung,Pendapatan Bruto (Rp)',
            '2026,9,Pengelola D,12,150000',
        ]);

        $this->post(route('pengunjung-wisata.import'), [
            'file' => UploadedFile::fake()->createWithContent('pengunjung.csv', $csv),
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('pengunjung_wisata', [
            'year' => 2026, 'month' => 9, 'number_of_visitors' => 12, 'gross_income' => 150000,
        ]);

        $response = $this->get(route('pengunjung-wisata.template'));
        $response->assertOk();
        $this->assertStringContainsString('template_import_pengunjung_wisata.xlsx', $response->headers->get('content-disposition'));
    }
}
