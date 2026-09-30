<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class PemberdayaanModuleFlowTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    protected function setUp(): void
    {
        $this->setUpUserDatabase();

        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('module_name');
            $table->string('filename');
            $table->string('status');
            $table->unsignedInteger('imported_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        foreach ($this->features() as $feature => $_) {
            foreach (['create', 'import'] as $action) {
                Permission::create(['name' => "$feature.$action", 'guard_name' => 'web']);
            }
        }
    }

    private function features(): array
    {
        return [
            'skps' => 'template_import_skps.xlsx',
            'kups' => 'template_import_kups.xlsx',
            'nilai-ekonomi' => 'template_import_nilai_ekonomi.xlsx',
            'perkembangan-kth' => 'template_import_perkembangan_kth.xlsx',
            'nilai-transaksi-ekonomi' => 'template_import_nilai_transaksi_ekonomi.xlsx',
        ];
    }

    public function test_all_five_templates_require_permission_and_download(): void
    {
        $this->get(route('skps.template'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->from('/dashboard')->get(route('skps.template'))
            ->assertRedirect('/dashboard')->assertSessionHas('error');

        $user->givePermissionTo(array_map(fn ($feature) => "$feature.create", array_keys($this->features())));
        foreach ($this->features() as $feature => $filename) {
            $response = $this->get(route("$feature.template"));
            $response->assertOk();
            $this->assertStringContainsString($filename, $response->headers->get('content-disposition'));
        }
    }

    public function test_existing_batches_can_be_committed_to_the_import_queue(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $user->givePermissionTo(array_map(fn ($feature) => "$feature.import", array_keys($this->features())));
        $this->actingAs($user);

        foreach ($this->features() as $feature => $_) {
            $batch = ImportBatch::create([
                'user_id' => $user->id,
                'module_name' => $feature,
                'filename' => 'batch-lama.csv',
                'status' => 'pending',
            ]);

            $this->post(route("$feature.commit-import", $batch))->assertRedirect();
            $this->assertDatabaseHas('import_batches', [
                'id' => $batch->id,
                'module_name' => $feature,
                'status' => 'processing',
            ]);
            Queue::assertPushed(ProcessImportBatch::class, fn ($job) => $job->batchId === $batch->id);
        }
    }
}
