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

        Schema::create('livestock_strains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');           // Name of the livestock strain (e.g., "Broiler", "Layer", "Dairy")
            $table->text('description')->nullable(); // Optional: Description of the strain category
            $table->string('status')->default('active')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livestock_strains');
    }
};
