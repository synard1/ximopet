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

        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 64)->unique(); // ex: SYSTEM, DEMO_XIMOPET
            $table->string('name', 191);
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('logo')->nullable();

            // for system tenant
            $table->string('domain')->unique()->nullable();
            $table->string('database')->unique()->nullable();
            $table->string('package')->nullable();
            $table->json('config')->nullable();

            $table->string('type', 32)->default('production');    // system|demo|production|sandbox|template
            $table->string('status', 32)->default('active');      // active|inactive

            $table->boolean('is_locked')->default(false);         // true untuk SYSTEM/DEMO

            // Opsional kolom identitas / kepemilikan
            $table->uuid('owner_user_id')->nullable()->index();

            $table->string('notes')->nullable();

            // Metadata fleksibel (plan, quota, alamat, dsb)
            $table->jsonb('metadata')->nullable();

            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            // Index tambahan yang sering dipakai
            $table->index(['status']);
            $table->index(['type']);

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
        Schema::dropIfExists('companies');
    }
};
