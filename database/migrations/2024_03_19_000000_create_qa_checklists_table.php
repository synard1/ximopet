<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qa_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('feature_name');
            $table->string('feature_category');
            $table->string('feature_subcategory')->nullable();
            $table->text('test_case');
            $table->text('url')->nullable();
            $table->text('test_steps')->nullable();
            $table->text('expected_result')->nullable();
            $table->string('test_type');
            $table->string('priority')->default('Medium');
            $table->string('status')->default('Not Tested');
            $table->text('notes')->nullable();
            $table->text('error_details')->nullable();
            $table->string('tester_name');
            $table->date('test_date');
            $table->string('environment')->default('Development');
            $table->string('browser')->nullable();
            $table->string('device')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('qa_checklists');
    }
};
