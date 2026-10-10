<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Kepegawaian\App\Exports\KgbExport;
use Modules\Kepegawaian\App\Exports\PegawaiExport;
use Modules\Kepegawaian\App\Exports\PegawaiTemplateExport;
use Modules\Kepegawaian\App\Exports\PensiunExport;
use Modules\Kepegawaian\App\Exports\RekapBulananExport;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class KepegawaianModuleFlowTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    protected function setUp(): void
    {
        $this->setUpUserDatabase();

        Schema::create('cdks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });
        DB::table('cdks')->insert(['id' => 42, 'nama' => 'CDK Uji']);

        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->string('nama_lengkap');
            $table->string('nip')->unique();
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('agama')->nullable();
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('status_pegawai')->nullable();
            $table->string('status_pernikahan')->nullable();
            $table->string('alamat')->nullable();
            $table->date('tmt_cpns')->nullable();
            $table->date('tmt_pns')->nullable();
            $table->string('skpd')->nullable();
            $table->unsignedBigInteger('bezetting_id')->nullable();
            $table->string('pangkat_golongan')->nullable();
            $table->integer('bup')->nullable();
            $table->string('status');
            $table->string('status_kedudukan');
            $table->string('unit_kerja')->nullable();
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
            $table->json('statistik_bezetting')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('rekap_bulanan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('periode_tahun');
            $table->integer('periode_bulan');
            $table->string('nip')->nullable();
            $table->string('nama_lengkap')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['demografi-pegawai.create', 'demografi-pegawai.export', 'proyeksi-gaji.export'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_employee_template_requires_authentication_and_permission(): void
    {
        $this->get(route('demografi-pegawai.template'))->assertRedirect(route('login'));

        $user = User::factory()->create(['cdk_id' => 42]);
        $this->actingAs($user);
        $this->from('/dashboard')->get(route('demografi-pegawai.template'))
            ->assertRedirect('/dashboard')->assertSessionHas('error');

        $user->givePermissionTo('demografi-pegawai.create');
        $response = $this->get(route('demografi-pegawai.template'));
        $response->assertOk();
        $this->assertStringContainsString('template-import-pegawai.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_employee_and_monthly_exports_download_through_the_module(): void
    {
        $user = User::factory()->create(['cdk_id' => 42]);
        $user->givePermissionTo(['demografi-pegawai.export', 'proyeksi-gaji.export']);
        $this->actingAs($user);

        $exports = [
            [route('demografi-pegawai.export'), 'data-demografi-pegawai-'],
            [route('proyeksi-gaji.export', ['year' => 2026, 'month' => 9]), 'Proyeksi_KGB_Pensiun_2026_9.xlsx'],
            [route('rekap-bulanan.export', ['year' => 2026, 'month' => 9]), 'rekap-kepegawaian-2026-9.xlsx'],
            [route('rekap-bulanan.export-bezetting', ['year' => 2026, 'month' => 9]), 'analisa-bezetting-2026-9.xlsx'],
        ];

        foreach ($exports as [$url, $filename]) {
            $response = $this->get($url);
            $response->assertOk();
            $this->assertStringContainsString($filename, $response->headers->get('content-disposition'));
        }
    }

    public function test_nik_and_nip_are_exported_as_exact_text(): void
    {
        $this->actingAs(User::factory()->create(['cdk_id' => 42]));

        $nip = '199001012015011001';
        $nik = '3578123456789001';
        Pegawai::create([
            'cdk_id' => 42,
            'nip' => $nip,
            'nik' => $nik,
            'nama_lengkap' => 'Pegawai Uji',
            'jenis_kelamin' => 'L',
            'status' => 'final',
            'status_kedudukan' => 'Aktif',
        ]);
        Pegawai::create([
            'cdk_id' => 42,
            'nip' => '012345678901234567',
            'nik' => '0012345678901234',
            'nama_lengkap' => 'Pegawai Uji 2',
            'jenis_kelamin' => 'L',
            'status' => 'final',
            'status_kedudukan' => 'Aktif',
        ]);

        $pegawaiSheet = $this->readExport(new PegawaiExport)->getActiveSheet();
        $this->assertSame(DataType::TYPE_NUMERIC, $pegawaiSheet->getCell('A2')->getDataType());
        $this->assertIdentifierCell($pegawaiSheet->getCell('B2'), $nip);
        $this->assertIdentifierCell($pegawaiSheet->getCell('D2'), $nik);
        $this->assertIdentifierCell($pegawaiSheet->getCell('B3'), '012345678901234567');
        $this->assertIdentifierCell($pegawaiSheet->getCell('D3'), '0012345678901234');

        DB::table('rekap_bulanan_pegawai')->insert([
            'cdk_id' => 42,
            'periode_tahun' => 2026,
            'periode_bulan' => 9,
            'nip' => $nip,
            'nama_lengkap' => 'Pegawai Uji',
        ]);
        $rekapSheet = $this->readExport(new RekapBulananExport(2026, 9))->getSheetByName('Detail Snapshot Pegawai');
        $this->assertIdentifierCell($rekapSheet->getCell('B2'), $nip);

        $kgbSheet = $this->readExport(new KgbExport([[
            'nip' => $nip,
            'nama' => 'Pegawai Uji',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'CDK Uji',
            'tmt_kgb_berikutnya' => '2026-09-01',
            'status' => 'Akan Datang',
        ]]))->getActiveSheet();
        $this->assertIdentifierCell($kgbSheet->getCell('B2'), $nip);

        $pensiunSheet = $this->readExport(new PensiunExport([[
            'nip' => $nip,
            'nama' => 'Pegawai Uji',
            'pangkat_golongan' => 'III/a',
            'unit_kerja' => 'CDK Uji',
            'tanggal_lahir' => '1968-09-01',
            'bup' => 58,
            'tmt_pensiun' => '2026-09-01',
            'status' => 'Akan Datang',
        ]]))->getActiveSheet();
        $this->assertIdentifierCell($pensiunSheet->getCell('B2'), $nip);

        $templateSheet = $this->readExport(new PegawaiTemplateExport)->getActiveSheet();
        $this->assertIdentifierCell($templateSheet->getCell('A2'), '199001012015011001');
        $this->assertIdentifierCell($templateSheet->getCell('C2'), '3578123456789001');
        $this->assertSame(NumberFormat::FORMAT_TEXT, $templateSheet->getStyle('A3')->getNumberFormat()->getFormatCode());
        $this->assertSame(NumberFormat::FORMAT_TEXT, $templateSheet->getStyle('C3')->getNumberFormat()->getFormatCode());
    }

    private function readExport(object $export): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'pegawai-export-');

        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    private function assertIdentifierCell(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, string $expected): void
    {
        $this->assertSame($expected, $cell->getValue());
        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame(NumberFormat::FORMAT_TEXT, $cell->getStyle()->getNumberFormat()->getFormatCode());
    }

    public function test_employee_csv_import_uses_the_module_importer(): void
    {
        $user = User::factory()->create(['cdk_id' => 42]);
        $user->givePermissionTo('demografi-pegawai.create');
        $this->actingAs($user);

        $csv = "nip,nama_lengkap,tempat_lahir,tanggal_lahir_yyyymmdd,jenis_kelamin_lp,agama,pendidikan_terakhir,status_pegawai,bup,status_kedudukan\n"
            . "NIP-UJI-01,Pegawai Uji,Malang,1988-01-01,L,Islam,S-1,PNS,60,Aktif\n";

        $this->from('/demografi-pegawai')->post(route('demografi-pegawai.import'), [
            'file' => UploadedFile::fake()->createWithContent('pegawai.csv', $csv),
        ])->assertRedirect('/demografi-pegawai');
        $this->assertEmpty(session('import_errors', []), json_encode(session('import_errors', [])));
        $this->assertNotNull(session('success'));

        $this->assertDatabaseHas('pegawais', [
            'nip' => 'NIP-UJI-01',
            'nama_lengkap' => 'Pegawai Uji',
            'cdk_id' => 42,
            'status' => 'final',
        ]);
    }
}
