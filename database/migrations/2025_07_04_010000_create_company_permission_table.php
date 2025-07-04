<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_permission', function (Blueprint $table) {
            $table->string('company_id');
            $table->uuid('permission_id');
            $table->primary(['company_id', 'permission_id']);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_permission');
    }
};
