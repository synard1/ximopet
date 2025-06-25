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
        Schema::table('feed_usages', function (Blueprint $table) {
            // Add missing columns
            $table->uuid('livestock_batch_id')->nullable()->after('livestock_id');
            $table->string('purpose')->default('feeding')->after('usage_date');
            $table->text('notes')->nullable()->after('purpose');
            $table->decimal('total_cost', 15, 2)->default(0)->after('total_quantity');

            // Add foreign key constraint
            $table->foreign('livestock_batch_id')->references('id')->on('livestock_batches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feed_usages', function (Blueprint $table) {
            $table->dropForeign(['livestock_batch_id']);
            $table->dropColumn(['livestock_batch_id', 'purpose', 'notes', 'total_cost']);
        });
    }
};
