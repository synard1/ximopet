<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndDateToKelompokTernakTable extends Migration
{
    public function up()
    {
        // Schema::table('kelompok_ternak', function (Blueprint $table) {
        //     $table->date('end_date')->nullable()->after('start_date');
        // });
    }

    public function down()
    {
        // Schema::table('kelompok_ternak', function (Blueprint $table) {
        //     $table->dropColumn('end_date');
        // });
    }
}