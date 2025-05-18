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
            $table->text('test_steps')->nullable();
            $table->text('expected_result')->nullable();
            $table->enum('test_type', [
                'CRUD',
                'UI/UX',
                'Functionality',
                'Performance',
                'Security',
                'Data Validation',
                'Error Handling',
                'Integration',
                'Business Logic'
            ]);
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->enum('status', ['Passed', 'Failed', 'Not Tested', 'Blocked'])->default('Not Tested');
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
