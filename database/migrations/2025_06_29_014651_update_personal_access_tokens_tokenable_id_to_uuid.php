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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop the existing index first
            $table->dropIndex(['tokenable_type', 'tokenable_id']);

            // Change tokenable_id from bigint to string (UUID)
            $table->string('tokenable_id', 36)->change();

            // Recreate the index
            $table->index(['tokenable_type', 'tokenable_id'], 'personal_access_tokens_tokenable_type_tokenable_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop the index
            $table->dropIndex(['tokenable_type', 'tokenable_id']);

            // Change tokenable_id back to bigint
            $table->unsignedBigInteger('tokenable_id')->change();

            // Recreate the index
            $table->index(['tokenable_type', 'tokenable_id'], 'personal_access_tokens_tokenable_type_tokenable_id_index');
        });
    }
};
