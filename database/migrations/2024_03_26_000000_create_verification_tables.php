<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('verification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('type')->comment('Type of verification: document, data, etc.');
            $table->json('requirements')->nullable()->comment('Required documents or data fields');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('model_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->uuid('model_id');
            $table->string('status')->default('pending');
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->json('verified_data')->nullable()->comment('Snapshot of verified data');
            $table->json('required_documents')->nullable()->comment('List of required documents');
            $table->json('verified_documents')->nullable()->comment('List of verified documents');
            $table->boolean('is_locked')->default(false)->comment('Prevents further modifications');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['model_type', 'model_id']);
        });

        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->uuid('model_id');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('action')->comment('verify, reject, unlock, etc.');
            $table->text('notes')->nullable();
            $table->json('changes')->nullable()->comment('Changes made during verification');
            $table->json('context')->nullable()->comment('Additional context about the verification');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('verification_logs');
        Schema::dropIfExists('model_verifications');
        Schema::dropIfExists('verification_rules');
    }
};
