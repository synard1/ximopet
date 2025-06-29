<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('menus')->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->string('location')->default('sidebar'); // sidebar, header, etc
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_role', function (Blueprint $table) {
            $table->uuid('menu_id');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->uuid('role_id'); // Changed to UUID to match roles table
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['menu_id', 'role_id']);
        });

        Schema::create('menu_permission', function (Blueprint $table) {
            $table->uuid('menu_id');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->uuid('permission_id'); // Changed to UUID to match permissions table
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['menu_id', 'permission_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_permission');
        Schema::dropIfExists('menu_role');
        Schema::dropIfExists('menus');
    }
};
