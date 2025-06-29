<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batch_workers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id');
            $table->uuid('worker_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('role')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // Default status adalah 'aktif'
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_workers');
    }
};
