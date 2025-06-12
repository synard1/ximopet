<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('livestock_purchase_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_purchase_id');
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('livestock_purchase_id', 'fk_purchase_status_history')
                ->references('id')
                ->on('livestock_purchases')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('livestock_purchase_status_histories');
    }
};
