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
        Schema::disableForeignKeyConstraints();

        Schema::create('farms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 64);
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('quantity')->default(0);
            $table->string('capacity')->default(0);
            $table->string('status');
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'code']);

            // Make code unique per company
            $table->unique(['company_id', 'code']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('farms');

        Schema::enableForeignKeyConstraints();
    }
};
