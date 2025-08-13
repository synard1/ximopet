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

        Schema::create('partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // Supplier or Buyer
            $table->string('code', 64);
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->uuid('created_by')->index();
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
        Schema::dropIfExists('partners');
    }
};
