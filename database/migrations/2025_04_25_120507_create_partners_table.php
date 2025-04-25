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
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

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
