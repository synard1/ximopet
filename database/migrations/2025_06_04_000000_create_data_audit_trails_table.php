<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('data_audit_trails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model_type'); // e.g. App\\Models\\Supply
            $table->uuid('model_id');
            $table->string('action'); // fix, rollback, update, delete, etc
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('rollback_to_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_audit_trails');
    }
};
