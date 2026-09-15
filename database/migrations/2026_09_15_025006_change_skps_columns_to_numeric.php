<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("UPDATE skps SET ps_area = 0 WHERE ps_area NOT REGEXP '^[0-9]+(\.[0-9]+)?$'");
        DB::statement("UPDATE skps SET number_of_kk = 0 WHERE number_of_kk NOT REGEXP '^[0-9]+$'");
        
        DB::statement("ALTER TABLE skps MODIFY ps_area DECIMAL(15,2) DEFAULT 0");
        DB::statement("ALTER TABLE skps MODIFY number_of_kk INT DEFAULT 0");
    }

    public function down()
    {
        DB::statement("ALTER TABLE skps MODIFY ps_area VARCHAR(255) NULL");
        DB::statement("ALTER TABLE skps MODIFY number_of_kk VARCHAR(255) NULL");
    }
};

