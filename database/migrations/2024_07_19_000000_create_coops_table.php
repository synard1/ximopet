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

        Schema::create('coops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('farm_id');
            $table->string('code');
            $table->string('name');
            $table->integer('capacity')->default(0);
            $table->text('notes')->nullable();
            $table->uuid('livestock_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('weight', 10, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('farm_id')->references('id')->on('farms');
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

        Schema::dropIfExists('coops');

        Schema::enableForeignKeyConstraints();
    }
};
