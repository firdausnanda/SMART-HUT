<?php

namespace Tests\Feature;

use App\Models\Penanaman;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\PenanamanOperatorSeeder;
use Modules\Penanaman\App\Exports\PenanamanExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\UsesIsolatedForestryDatabase;
use Tests\TestCase;

class PenanamanModuleTest extends TestCase
{
    use UsesIsolatedForestryDatabase { setUp as setUpForestry; }

    protected function setUp(): void
    {
        $this->setUpForestry();
        (require database_path('migrations/2026_01_14_072443_create_sumber_dana_table.php'))->up();
        (require database_path('migrations/2026_11_24_000001_create_penanaman_tables.php'))->up();
        (require database_path('migrations/2026_11_24_000002_add_penanaman_permissions.php'))->up();
        (require database_path('migrations/2026_11_24_000003_add_fund_source_to_penanaman.php'))->up();
        (require database_path('migrations/2026_09_14_135832_create_import_batches_table.php'))->up();
        (require database_path('migrations/2026_09_14_135832_create_import_staging_rows_table.php'))->up();
        (require database_path('migrations/2026_09_19_000001_add_imported_count_to_import_batches.php'))->up();
        (require database_path('migrations/2026_11_24_000004_add_penanaman_transfer_permissions.php'))->up();
        (require database_path('migrations/2026_11_24_000005_add_penanaman_delete_permission.php'))->up();

        DB::table('m_sumber_dana')->insert([['name' => 'APBD'], ['name' => 'CSR']]);
        DB::table('m_regencies')->insert(['id' => 3502, 'province_id' => 35, 'name' => 'Kabupaten Kedua']);
        DB::table('m_districts')->insert(['id' => 350201, 'regency_id' => 3502, 'name' => 'Kecamatan Kedua']);
        DB::table('m_villages')->insert(['id' => 35020101, 'district_id' => 350201, 'name' => 'Desa Kedua']);
    }

    private function user(string $role, ?string $regency = null): User
    {
        $user = User::create([
            'name' => $role . $regency,
            'username' => uniqid('user'),
            'email' => uniqid('mail') . '@example.test',
            'password' => 'password',
            'regency_id' => $regency,
        ]);
        $user->assignRole($role);
        return $user;
    }

    private function completePayload(string $regency = '3501'): array
    {
        return [
            'year' => 2026,
            'month' => 11,
            'regency_id' => $regency,
            'district_id' => $regency === '3501' ? '350101' : '350201',
            'village_id' => $regency === '3501' ? '35010101' : '35020101',
            'location_name' => 'Lahan sekolah',
            'realization_ha' => 1.25,
            'fund_source' => 'APBD',
            'species' => [
                ['species_name' => 'Jati', 'batang' => 100],
                ['species_name' => 'Mahoni', 'batang' => 50],
            ],
        ];
    }

    private function importFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Tahun', 'Bulan (Angka)', 'Nama Kabupaten', 'Nama Kecamatan', 'Nama Desa',
                'Nama Lokasi', 'Koordinat', 'Realisasi (Ha)', 'Sumber Dana'],
            ...$rows,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'penanaman-import-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path, 'penanaman.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null, true
        );
    }

    public function test_routes_are_owned_by_standalone_penanaman_module(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'penanaman.'));

        $this->assertCount(22, $routes);
        foreach ($routes as $route) {
            $this->assertStringStartsWith('Modules\\Penanaman\\', $route->getActionName());
        }
    }

    public function test_create_route_is_not_captured_as_report_id(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');

        $this->actingAs($operator)->get(route('penanaman.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Form')->etc());

        $this->actingAs($operator)->get('/penanaman/bukan-id')->assertNotFound();
    }

    public function test_operator_is_confined_to_own_regency_even_with_forged_requests(): void
    {
        $first = $this->user('operator_kabupaten', '3501');
        $second = $this->user('operator_kabupaten', '3502');
        $this->actingAs($second)->put(route('penanaman.targets.update', ['regency' => '3502', 'year' => 2026]), [
            'target_ha' => 10, 'target_batang' => 1000,
        ])->assertRedirect();
        $this->actingAs($first)->put(route('penanaman.targets.update', ['regency' => '3502', 'year' => 2026]), [
            'target_ha' => 99, 'target_batang' => 9999,
        ])->assertForbidden();
        $this->assertDatabaseHas('penanaman_targets', ['regency_id' => '3502', 'target_batang' => 1000]);

        $this->actingAs($second)->post(route('penanaman.store'), $this->completePayload('3502'))->assertRedirect();
        $otherReport = Penanaman::where('regency_id', '3502')->firstOrFail();
        $this->actingAs($first)->get(route('penanaman.show', $otherReport))->assertNotFound();
        $this->actingAs($first)->put(route('penanaman.update', $otherReport), $this->completePayload('3501'))->assertNotFound();
        $this->actingAs($first)->post(route('penanaman.submit', $otherReport))->assertNotFound();
        $this->actingAs($first)->get(route('penanaman.targets.value', ['regency_id' => '3502', 'year' => 2026]))->assertForbidden();

        $this->actingAs($first)->post(route('penanaman.store'), [
            'year' => 2026, 'month' => 11, 'regency_id' => '3502',
        ])->assertRedirect();
        $this->assertDatabaseHas('penanaman', ['regency_id' => '3501', 'created_by' => $first->id]);
        $this->actingAs($first)->post(route('penanaman.store'), [
            ...$this->completePayload(), 'district_id' => '350201',
        ])->assertSessionHasErrors('district_id');
    }

    public function test_only_drafts_in_own_region_can_be_deleted(): void
    {
        $first = $this->user('operator_kabupaten', '3501');
        $second = $this->user('operator_kabupaten', '3502');
        $admin = $this->user('admin_provinsi');

        $this->actingAs($first)->post(route('penanaman.store'), $this->completePayload())->assertRedirect();
        $own = Penanaman::where('regency_id', '3501')->firstOrFail();
        $this->actingAs($second)->post(route('penanaman.store'), $this->completePayload('3502'))->assertRedirect();
        $other = Penanaman::where('regency_id', '3502')->firstOrFail();

        $this->actingAs($first)->get(route('penanaman.show', $own))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Show')->where('canDelete', true)->etc());
        $this->actingAs($first)->delete(route('penanaman.destroy', $other))->assertNotFound();
        $this->assertDatabaseHas('penanaman', ['id' => $other->id]);

        $this->actingAs($first)->put(route('penanaman.targets.update', ['regency' => '3501', 'year' => 2026]), [
            'target_ha' => 5, 'target_batang' => 500,
        ])->assertRedirect();
        $this->actingAs($first)->post(route('penanaman.submit', $own))->assertRedirect();
        $this->actingAs($first)->delete(route('penanaman.destroy', $own))->assertStatus(409);
        $this->actingAs($admin)->delete(route('penanaman.destroy', $own))->assertStatus(409);
        $this->assertDatabaseHas('penanaman', ['id' => $own->id, 'status' => 'final']);

        $this->actingAs($admin)->post(route('penanaman.return', $own), ['alasan' => 'Perlu koreksi'])->assertRedirect();
        $this->actingAs($first)->delete(route('penanaman.destroy', $own))->assertRedirect(route('penanaman.index'));
        $this->assertDatabaseMissing('penanaman', ['id' => $own->id]);
        $this->assertDatabaseMissing('penanaman_species', ['penanaman_id' => $own->id]);
        $this->assertDatabaseMissing('penanaman_events', ['penanaman_id' => $own->id]);

        $this->actingAs($admin)->delete(route('penanaman.destroy', $other))->assertRedirect(route('penanaman.index'));
        $this->assertDatabaseMissing('penanaman', ['id' => $other->id]);
    }

    public function test_draft_submission_without_note_return_and_resubmission_keep_history_and_permissions(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');
        $admin = $this->user('admin_provinsi');
        $this->actingAs($operator)->post(route('penanaman.store'), ['year' => 2026, 'month' => 11])->assertRedirect();
        $report = Penanaman::firstOrFail();
        $this->actingAs($operator)->put(route('penanaman.update', $report), [
            'year' => 2026, 'month' => 11,
            'species' => [['species_name' => 'Jati', 'batang' => '']],
        ])->assertRedirect();
        $this->actingAs($operator)->post(route('penanaman.submit', $report))
            ->assertSessionHasErrors();
        $this->actingAs($operator)->put(route('penanaman.update', $report), $this->completePayload())->assertRedirect();
        $this->actingAs($operator)->post(route('penanaman.submit', $report))
            ->assertSessionHasErrors('target');
        $this->actingAs($operator)->put(route('penanaman.targets.update', ['regency' => '3501', 'year' => 2026]), [
            'target_ha' => 5, 'target_batang' => 500,
        ])->assertRedirect();
        $this->actingAs($operator)->post(route('penanaman.submit', $report))->assertRedirect();
        $this->assertDatabaseHas('penanaman', ['id' => $report->id, 'status' => 'final']);
        $this->actingAs($operator)->get(route('penanaman.edit', $report))->assertForbidden();
        $this->actingAs($operator)->post(route('penanaman.return', $report), ['alasan' => 'Perbaiki'])->assertRedirect();
        $this->assertDatabaseHas('penanaman', ['id' => $report->id, 'status' => 'final']);
        $this->actingAs($admin)->post(route('penanaman.return', $report), ['alasan' => 'Jumlah perlu diperbaiki'])->assertRedirect();
        $withoutSpecies = $this->completePayload();
        unset($withoutSpecies['species']);
        $this->actingAs($admin)->put(route('penanaman.update', $report), [
            ...$withoutSpecies, 'realization_ha' => 2.5,
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('penanaman.submit', $report))->assertRedirect();
        $this->assertDatabaseHas('penanaman', ['id' => $report->id, 'status' => 'final', 'realization_ha' => 2.5]);
        $this->assertSame(['submitted', 'returned', 'submitted'], $report->events()->pluck('action')->all());
        $this->assertSame(['Laporan diajukan menjadi final.', 'Jumlah perlu diperbaiki', 'Laporan diajukan menjadi final.'], $report->events()->pluck('note')->all());
        $this->assertSame(150, $report->species()->sum('batang'));
    }

    public function test_submission_requires_fund_source_but_not_species(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');
        $this->actingAs($operator)->get(route('penanaman.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Form')->has('sumberDana', 2)->etc());

        $payload = $this->completePayload();
        unset($payload['species'], $payload['fund_source']);
        $this->actingAs($operator)->post(route('penanaman.store'), $payload)->assertRedirect();
        $report = Penanaman::firstOrFail();
        $this->assertSame(0, $report->species()->count());

        $this->actingAs($operator)->put(route('penanaman.targets.update', ['regency' => '3501', 'year' => 2026]), [
            'target_ha' => 5, 'target_batang' => 500,
        ])->assertRedirect();
        $this->actingAs($operator)->post(route('penanaman.submit', $report))
            ->assertSessionHasErrors('fund_source');

        $this->actingAs($operator)->put(route('penanaman.update', $report), [
            ...$payload, 'fund_source' => 'Tidak ada',
        ])->assertSessionHasErrors('fund_source');
        $this->actingAs($operator)->put(route('penanaman.update', $report), [
            ...$payload, 'fund_source' => 'APBD',
        ])->assertRedirect();
        $this->actingAs($operator)->post(route('penanaman.submit', $report))->assertRedirect();
        $this->assertDatabaseHas('penanaman', [
            'id' => $report->id, 'fund_source' => 'APBD', 'status' => 'final',
        ]);
        $this->assertSame(0, $report->species()->count());
    }

    public function test_export_is_limited_to_operator_region(): void
    {
        $first = $this->user('operator_kabupaten', '3501');
        $second = $this->user('operator_kabupaten', '3502');
        $this->actingAs($first)->post(route('penanaman.store'), $this->completePayload())->assertRedirect();
        $this->actingAs($second)->post(route('penanaman.store'), $this->completePayload('3502'))->assertRedirect();

        $export = new PenanamanExport($first, ['year' => 2026]);
        $this->assertSame(['3501'], $export->query()->pluck('regency_id')->map(strval(...))->unique()->values()->all());
        $this->actingAs($first)->get(route('penanaman.export', ['year' => 2026]))->assertOk()
            ->assertHeader('content-disposition');
        $this->actingAs($first)->get(route('penanaman.export', ['regency_id' => '3502']))->assertForbidden();
        $this->actingAs($first)->get(route('penanaman.import-template'))->assertOk();
    }

    public function test_import_preview_and_commit_only_accept_own_valid_rows_as_drafts(): void
    {
        config(['queue.default' => 'sync']);
        $first = $this->user('operator_kabupaten', '3501');
        $second = $this->user('operator_kabupaten', '3502');
        $file = $this->importFile([
            [2026, 11, 'KABUPATEN TRENGGALEK', 'Kecamatan Uji', 'Desa Uji', 'Lahan impor', '-8.1,111.2', 1.5, 'APBD'],
            [2026, 11, 'Kabupaten Kedua', 'Kecamatan Kedua', 'Desa Kedua', 'Lahan luar', '', 2, 'CSR'],
            [2026, 11, 'KABUPATEN TRENGGALEK', 'Kecamatan Kedua', 'Desa Kedua', 'Kecamatan salah', '', 1, 'APBD'],
        ]);

        try {
            $this->actingAs($first)->post(route('penanaman.preview-import'), ['file' => $file])
                ->assertSessionHasNoErrors();
        } finally {
            if (file_exists($file->getPathname())) unlink($file->getPathname());
        }

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(['valid', 'invalid', 'invalid'], $batch->stagingRows()->orderBy('row_number')->pluck('status')->all());
        $this->actingAs($first)->get(route('penanaman.show-preview', $batch))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/ImportPreview')->has('rows.data', 3)->etc());
        $this->actingAs($second)->get(route('penanaman.show-preview', $batch))->assertNotFound();
        $this->actingAs($second)->post(route('penanaman.commit-import', $batch))->assertNotFound();
        $this->actingAs($first)->post(route('penanaman.commit-import', $batch))->assertRedirect();
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'completed', 'imported_count' => 1]);
        $this->assertDatabaseHas('penanaman', [
            'location_name' => 'Lahan impor', 'regency_id' => '3501', 'status' => 'draft',
            'fund_source' => 'APBD', 'created_by' => $first->id,
        ]);
        $this->assertDatabaseCount('penanaman', 1);
    }

    public function test_recap_counts_targets_once_and_only_final_reports(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');
        $this->actingAs($operator)->put(route('penanaman.targets.update', ['regency' => '3501', 'year' => 2026]), [
            'target_ha' => 10, 'target_batang' => 1000,
        ])->assertRedirect();
        foreach (range(1, 3) as $number) {
            $this->actingAs($operator)->post(route('penanaman.store'), [
                ...$this->completePayload(), 'location_name' => "Lokasi $number",
            ])->assertRedirect();
        }
        $reports = Penanaman::orderBy('id')->get();
        foreach ($reports->take(2) as $report) {
            $this->actingAs($operator)->post(route('penanaman.submit', $report))->assertRedirect();
        }
        $this->actingAs($operator)->get(route('penanaman.index', ['year' => 2026]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Penanaman/Index')
                ->where('stats.target_ha', 10)
                ->where('stats.target_batang', 1000)
                ->where('stats.realization_ha', 2.5)
                ->where('stats.realization_batang', 300)
                ->etc());
    }

    public function test_target_luas_can_be_saved_without_target_batang(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');
        $route = route('penanaman.targets.update', ['regency' => '3501', 'year' => 2026]);

        $this->actingAs($operator)->put($route, ['target_ha' => 5])->assertRedirect();
        $this->assertDatabaseHas('penanaman_targets', [
            'regency_id' => '3501', 'year' => 2026, 'target_ha' => 5, 'target_batang' => 0,
        ]);

        $this->actingAs($operator)->put($route, ['target_ha' => 6, 'target_batang' => 500])->assertRedirect();
        $this->actingAs($operator)->put($route, ['target_ha' => 7])->assertRedirect();
        $this->assertDatabaseHas('penanaman_targets', [
            'regency_id' => '3501', 'year' => 2026, 'target_ha' => 7, 'target_batang' => 500,
        ]);
    }

    public function test_target_button_uses_selected_year_and_region_completion(): void
    {
        $first = $this->user('operator_kabupaten', '3501');
        $second = $this->user('operator_kabupaten', '3502');
        $admin = $this->user('admin_provinsi');

        $this->actingAs($first)->get(route('penanaman.index', ['year' => 2026]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', true)->etc());
        $this->actingAs($first)->put(route('penanaman.targets.update', ['regency' => '3501', 'year' => 2026]), [
            'target_ha' => 0,
        ])->assertRedirect();
        $this->actingAs($first)->get(route('penanaman.index', ['year' => 2026]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', false)->etc());
        $this->actingAs($second)->get(route('penanaman.index', ['year' => 2026]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', true)->etc());
        $this->actingAs($admin)->get(route('penanaman.index', ['year' => 2026]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', true)->etc());
        $this->actingAs($admin)->get(route('penanaman.index', ['year' => 2026, 'regency_id' => '3501']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', false)->etc());

        $this->actingAs($second)->put(route('penanaman.targets.update', ['regency' => '3502', 'year' => 2026]), [
            'target_ha' => 5,
        ])->assertRedirect();
        $this->actingAs($admin)->get(route('penanaman.index', ['year' => 2026]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', false)->etc());
        $this->actingAs($admin)->get(route('penanaman.index', ['year' => 2025]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Index')->where('targetMissing', true)->etc());
    }

    public function test_admin_provisions_missing_region_account_with_username_and_password(): void
    {
        Notification::fake();
        $admin = $this->user('admin_provinsi');
        $this->actingAs($admin)->get(route('penanaman.accounts.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Accounts')->has('regions', 2)->etc());
        $this->actingAs($admin)->post(route('penanaman.accounts.store'), [
            'regency_id' => '3501', 'name' => 'Operator Trenggalek', 'email' => 'operator@trenggalek.go.id',
            'username' => 'operator.trenggalek', 'password' => 'Kuat123!aman',
            'password_confirmation' => 'Kuat123!aman',
        ])->assertRedirect();
        $account = User::where('username', 'operator.trenggalek')->firstOrFail();
        $this->assertTrue($account->hasRole('operator_kabupaten'));
        $this->assertSame('3501', $account->regency_id);
        $this->assertTrue(Hash::check('Kuat123!aman', $account->password));
        Notification::assertNotSentTo($account, ResetPassword::class);
        $this->actingAs($admin)->post(route('penanaman.accounts.resend', $account))->assertRedirect();
        Notification::assertSentTo($account, ResetPassword::class);
        $admin->givePermissionTo(Permission::create(['name' => 'users.edit', 'guard_name' => 'web']));
        $this->actingAs($admin)->put(route('users.update', $account), [
            'name' => 'Operator Baru', 'username' => $account->username,
            'email' => 'operator-baru@trenggalek.go.id', 'role' => 'operator_kabupaten',
            'permissions' => ['penanaman.view', 'users.delete'],
        ])->assertRedirect();
        $account->refresh();
        $this->assertSame('operator-baru@trenggalek.go.id', $account->email);
        $this->assertCount(0, $account->permissions);
        $this->actingAs($admin)->post(route('penanaman.accounts.store'), [
            'regency_id' => '3501', 'name' => 'Duplikat', 'email' => 'lain@example.test',
            'username' => 'operator.lain', 'password' => 'Kuat123!aman',
            'password_confirmation' => 'Kuat123!aman',
        ])->assertSessionHasErrors('regency_id');
        $this->actingAs($admin)->post(route('penanaman.accounts.store'), [
            'regency_id' => '3502', 'name' => 'Operator Kedua', 'email' => 'kedua@example.test',
            'username' => 'operator.trenggalek', 'password' => 'Kuat123!aman',
            'password_confirmation' => 'Kuat123!aman',
        ])->assertSessionHasErrors('username');
        $this->actingAs($admin)->post(route('penanaman.accounts.store'), [
            'regency_id' => '3502', 'name' => 'Operator Kedua', 'email' => 'kedua@example.test',
            'username' => 'operator.kedua', 'password' => 'lemah',
            'password_confirmation' => 'berbeda',
        ])->assertSessionHasErrors('password');
    }

    public function test_operator_seeder_creates_missing_regions_without_changing_existing_accounts(): void
    {
        $existing = $this->user('operator_kabupaten', '3501');
        $original = $existing->only(['name', 'username', 'email', 'password']);
        DB::table('m_regencies')->insert(['id' => 3573, 'province_id' => 35, 'name' => 'KOTA MALANG']);

        $this->seed(PenanamanOperatorSeeder::class);

        $seeded = User::role('operator_kabupaten')->where('regency_id', '3502')->firstOrFail();
        $this->assertSame('operator_kabupaten_kedua', $seeded->username);
        $this->assertSame('operator_kabupaten_kedua@smarthut.jatim.prov', $seeded->email);
        $this->assertSame('Operator Kabupaten Kedua', $seeded->name);
        $this->assertTrue(Hash::check('password_kabupaten_kedua', $seeded->password));
        $city = User::role('operator_kabupaten')->where('regency_id', '3573')->firstOrFail();
        $this->assertSame('operator_kota_malang', $city->username);
        $this->assertSame('operator_kota_malang@smarthut.jatim.prov', $city->email);
        $this->assertTrue(Hash::check('password_kota_malang', $city->password));
        $this->assertSame($original, $existing->fresh()->only(['name', 'username', 'email', 'password']));
        $this->assertNotSame($seeded->password, $existing->password);

        $this->seed(PenanamanOperatorSeeder::class);
        $this->assertSame(3, User::role('operator_kabupaten')->count());
        $this->assertSame($seeded->password, $seeded->fresh()->password);
    }

    public function test_admin_updates_operator_account_and_rejects_duplicate_region_or_credentials(): void
    {
        $admin = $this->user('admin_provinsi');
        $operator = $this->user('operator_kabupaten', '3501');
        $other = $this->user('operator_kabupaten', '3502');
        $originalPassword = $operator->password;
        $payload = [
            'regency_id' => '3501', 'name' => 'Operator Baru',
            'email' => 'BARU@EXAMPLE.TEST', 'username' => 'operator.baru',
            'password' => '', 'password_confirmation' => '',
        ];

        $this->actingAs($operator)->put(route('penanaman.accounts.update', $operator), $payload)
            ->assertRedirect('/');
        $this->actingAs($admin)->put(route('penanaman.accounts.update', $operator), [
            ...$payload, 'regency_id' => '3502',
        ])->assertSessionHasErrors('regency_id');
        $this->actingAs($admin)->put(route('penanaman.accounts.update', $operator), [
            ...$payload, 'username' => $other->username,
        ])->assertSessionHasErrors('username');
        $this->actingAs($admin)->put(route('penanaman.accounts.update', $operator), $payload)->assertRedirect();
        $operator->refresh();
        $this->assertSame('Operator Baru', $operator->name);
        $this->assertSame('baru@example.test', $operator->email);
        $this->assertSame('operator.baru', $operator->username);
        $this->assertSame($originalPassword, $operator->password);

        $this->actingAs($admin)->put(route('penanaman.accounts.update', $operator), [
            ...$payload, 'password' => 'Kuat123!aman', 'password_confirmation' => 'Kuat123!aman',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('Kuat123!aman', $operator->fresh()->password));
    }

    public function test_admin_deletes_operator_account_without_deleting_reports(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');
        $admin = $this->user('admin_provinsi');
        $this->actingAs($operator)->post(route('penanaman.store'), $this->completePayload())->assertRedirect();
        $reportId = Penanaman::firstOrFail()->id;

        $this->actingAs($operator)->delete(route('penanaman.accounts.destroy', $operator))
            ->assertRedirect('/');
        $this->actingAs($admin)->delete(route('penanaman.accounts.destroy', $admin))->assertNotFound();
        $this->actingAs($admin)->delete(route('penanaman.accounts.destroy', $operator))->assertRedirect();

        $this->assertNull(User::find($operator->id));
        $this->assertDatabaseHas('penanaman', ['id' => $reportId, 'regency_id' => '3501']);
        $this->actingAs($admin)->get(route('penanaman.accounts.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Penanaman/Accounts')
                ->where('regions.0.operators', [])->etc());
    }

    public function test_cdk_roles_do_not_get_module_access_even_with_a_direct_permission(): void
    {
        $cdk = Role::create(['name' => 'admin_cdk', 'guard_name' => 'web']);
        $cdk->givePermissionTo('penanaman.view');
        $user = $this->user('admin_cdk');
        $this->actingAs($user)->get(route('penanaman.index'))->assertForbidden();
    }

    public function test_operator_is_redirected_from_legacy_dashboard_and_export(): void
    {
        $operator = $this->user('operator_kabupaten', '3501');
        $this->post(route('login'), ['username' => $operator->username, 'password' => 'password'])
            ->assertRedirect(route('penanaman.index'));
        $this->actingAs($operator)->get(route('dashboard'))->assertRedirect(route('penanaman.index'));
        $this->actingAs($operator)->get(route('dashboard.export-rehab-lahan'))
            ->assertRedirect(route('penanaman.index'));
    }
}
