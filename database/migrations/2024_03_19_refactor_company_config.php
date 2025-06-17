<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop existing config columns if any
            $table->dropColumn(['mutation_config']);

            // Add new config column
            $table->json('config')->nullable()->after('package');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('config');
            $table->json('mutation_config')->nullable()->after('package');
        });
    }
};
