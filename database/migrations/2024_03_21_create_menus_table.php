<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->string('location')->default('sidebar'); // sidebar, header, etc
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_role', function (Blueprint $table) {
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->primary(['menu_id', 'role_id']);
        });

        Schema::create('menu_permission', function (Blueprint $table) {
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
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
