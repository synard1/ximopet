<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('route_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('route_path');
            $table->string('method');
            $table->json('middleware')->nullable();
            $table->string('permission_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['route_path', 'method']);
            $table->index(['company_id', 'is_active']);
            $table->index(['permission_name', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('route_permissions');
    }
};
