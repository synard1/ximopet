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
        Schema::table('livestock_sales', function (Blueprint $table) {
            // Penomoran dan referensi
            $table->string('invoice_number')->nullable()->after('company_id')->index();
            $table->string('do_number')->nullable()->after('invoice_number'); // delivery order number

            // Lokasi dan batch
            $table->uuid('farm_id')->nullable()->after('do_number');
            $table->uuid('coop_id')->nullable()->after('farm_id');
            $table->uuid('livestock_batch_id')->nullable()->after('livestock_id');

            // Pengiriman
            $table->dateTime('delivery_date')->nullable()->after('date');
            $table->text('delivery_address')->nullable()->after('delivery_date');
            $table->uuid('expedition_id')->nullable()->after('delivery_address');
            $table->decimal('expedition_fee', 15, 2)->default(0)->after('expedition_id');

            // Total dan nilai
            $table->integer('total_quantity')->default(0)->after('expedition_fee');
            $table->decimal('total_weight', 15, 2)->default(0)->after('total_quantity');
            $table->decimal('total_amount', 15, 2)->default(0)->after('total_weight');

            // Pembayaran
            $table->string('payment_method')->nullable()->after('total_amount');
            $table->string('payment_status')->default('unpaid')->after('payment_method');
            $table->dateTime('due_date')->nullable()->after('payment_status');

            // Approval workflow
            $table->uuid('approved_by')->nullable()->after('data');
            $table->dateTime('approved_at')->nullable()->after('approved_by');

            // Indexes untuk performa
            $table->index(['farm_id', 'coop_id']);
            $table->index(['livestock_id', 'livestock_batch_id']);
            $table->index(['customer_id', 'date']);
            $table->index(['status', 'payment_status']);
            $table->index(['due_date', 'payment_status']);

            // Foreign key constraints
            $table->foreign('farm_id')->references('id')->on('farms')->onDelete('set null');
            $table->foreign('coop_id')->references('id')->on('coops')->onDelete('set null');
            $table->foreign('livestock_batch_id')->references('id')->on('livestock_batches')->onDelete('set null');
            $table->foreign('expedition_id')->references('id')->on('expeditions')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livestock_sales', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['farm_id']);
            $table->dropForeign(['coop_id']);
            $table->dropForeign(['livestock_batch_id']);
            $table->dropForeign(['expedition_id']);
            $table->dropForeign(['approved_by']);

            // Drop indexes
            $table->dropIndex(['farm_id', 'coop_id']);
            $table->dropIndex(['livestock_id', 'livestock_batch_id']);
            $table->dropIndex(['customer_id', 'date']);
            $table->dropIndex(['status', 'payment_status']);
            $table->dropIndex(['due_date', 'payment_status']);
            $table->dropIndex(['invoice_number']);

            // Drop columns
            $table->dropColumn([
                'invoice_number',
                'do_number',
                'farm_id',
                'coop_id',
                'livestock_batch_id',
                'delivery_date',
                'delivery_address',
                'expedition_id',
                'expedition_fee',
                'total_quantity',
                'total_weight',
                'total_amount',
                'payment_method',
                'payment_status',
                'due_date',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
