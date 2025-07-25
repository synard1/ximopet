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
        Schema::create('recording_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('livestock_id');
            $table->uuid('recording_id');
            $table->uuid('livestock_batch_id')->nullable();
            $table->date('date');
            $table->integer('quantity')->default(0);
            $table->decimal('weight', 15, 2)->default(0);
            $table->float('price', 15, 2)->default(0);
            $table->string('status', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['livestock_id', 'recording_id'], 'idx_livestock_recording');
            $table->index('livestock_id');
            $table->index('recording_id');
            $table->index('date');
            $table->index('status');
            $table->index('livestock_batch_id');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('livestock_id')->references('id')->on('livestocks')->onDelete('cascade');
            $table->foreign('recording_id')->references('id')->on('recordings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('livestock_batch_id')->references('id')->on('livestock_batches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recording_sales');
    }
};
