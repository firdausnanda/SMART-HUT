<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait UsesIsolatedForestryDatabase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    protected function setUp(): void
    {
        $this->setUpUserDatabase();

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
        Schema::create('m_jenis_produksi', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('pbphh', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id');
            $table->string('name');
            $table->string('number');
            $table->string('investment_value');
            $table->integer('number_of_workers');
            $table->boolean('present_condition');
            $table->string('status')->default('draft');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('pbphh_jenis_produksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pbphh_id');
            $table->unsignedBigInteger('jenis_produksi_id');
            $table->decimal('kapasitas_ijin', 15, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('realisasi_pnbp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('id_pengelola_wisata');
            $table->string('types_of_forest_products');
            $table->string('pnbp_target');
            $table->string('pnbp_realization');
            $table->string('status')->default('draft');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });

        DB::table('m_provinces')->insert(['id' => 35, 'name' => 'Jawa Timur']);
        DB::table('m_regencies')->insert(['id' => 3501, 'province_id' => 35, 'name' => 'KABUPATEN TRENGGALEK']);
        DB::table('m_districts')->insert(['id' => 350101, 'regency_id' => 3501, 'name' => 'Kecamatan Uji']);
        DB::table('m_villages')->insert(['id' => 35010101, 'district_id' => 350101, 'name' => 'Desa Uji']);
        DB::table('m_pengelola_wisata')->insert(['id' => 1, 'name' => 'Pengelola Uji']);
        DB::table('m_jenis_produksi')->insert(['id' => 1, 'name' => 'Produksi Uji']);

        foreach (['pbphh.view', 'pbphh.create', 'pbphh.edit', 'pbphh.delete',
            'realisasi-pnbp.view', 'realisasi-pnbp.create', 'realisasi-pnbp.edit',
            'users.delete'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());
        $provinceAdmin = Role::create(['name' => 'admin_provinsi', 'guard_name' => 'web']);
        $provinceAdmin->givePermissionTo('users.delete');
        Role::create(['name' => 'pelaksana', 'guard_name' => 'web']);
    }
}
