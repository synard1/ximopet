<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Table untuk menyimpan analytics harian
        Schema::create('daily_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->uuid('livestock_id');
            $table->uuid('farm_id');
            $table->uuid('coop_id');

            // Mortality Analytics
            $table->integer('mortality_count')->default(0);
            $table->decimal('mortality_rate', 5, 2)->default(0); // percentage
            $table->integer('cumulative_mortality')->default(0);

            // Sales Analytics
            $table->integer('sales_count')->default(0);
            $table->decimal('sales_weight', 10, 2)->default(0);
            $table->decimal('sales_revenue', 15, 2)->default(0);

            // Performance Analytics
            $table->integer('current_population')->default(0);
            $table->decimal('average_weight', 10, 2)->default(0);
            $table->decimal('feed_consumption', 10, 2)->default(0);
            $table->decimal('fcr', 5, 3)->default(0); // Feed Conversion Ratio
            $table->integer('age_days')->default(0);

            // Production Analytics
            $table->decimal('daily_weight_gain', 8, 2)->default(0);
            $table->decimal('production_index', 8, 2)->default(0);
            $table->decimal('efficiency_score', 5, 2)->default(0);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('livestock_id')->references('id')->on('livestocks');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->unique(['date', 'livestock_id']);
            $table->index(['date', 'farm_id', 'coop_id']);
        });

        // Table untuk analytics agregat per periode
        Schema::create('period_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id');
            $table->uuid('farm_id');
            $table->uuid('coop_id');
            $table->string('period_type'); // weekly, monthly, cycle
            $table->date('period_start');
            $table->date('period_end');

            // Consolidated Metrics
            $table->integer('total_mortality')->default(0);
            $table->decimal('avg_mortality_rate', 5, 2)->default(0);
            $table->integer('total_sales')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('avg_fcr', 5, 3)->default(0);
            $table->decimal('avg_daily_gain', 8, 2)->default(0);
            $table->decimal('final_weight', 10, 2)->default(0);
            $table->decimal('total_feed_cost', 15, 2)->default(0);
            $table->decimal('profit_margin', 15, 2)->default(0);

            // Performance Rankings
            $table->integer('mortality_rank')->nullable();
            $table->integer('growth_rank')->nullable();
            $table->integer('efficiency_rank')->nullable();

            $table->json('detailed_metrics')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('livestock_id')->references('id')->on('livestocks');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->index(['period_type', 'period_start', 'period_end']);
        });

        // Table untuk benchmark data
        Schema::create('performance_benchmarks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('strain_id'); // referensi ke livestock_strains
            $table->integer('age_week');
            $table->decimal('target_weight', 8, 2);
            $table->decimal('target_fcr', 5, 3);
            $table->decimal('target_mortality_rate', 5, 2);
            $table->decimal('target_daily_gain', 6, 2);

            // Acceptable Ranges
            $table->decimal('weight_min', 8, 2)->nullable();
            $table->decimal('weight_max', 8, 2)->nullable();
            $table->decimal('fcr_min', 5, 3)->nullable();
            $table->decimal('fcr_max', 5, 3)->nullable();
            $table->decimal('mortality_max', 5, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('additional_targets')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('strain_id')->references('id')->on('livestock_strains');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->unique(['strain_id', 'age_week']);
        });

        // Table untuk alert dan recommendations
        Schema::create('analytics_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('livestock_id')->nullable();
            $table->uuid('farm_id')->nullable();
            $table->uuid('coop_id')->nullable();
            $table->string('alert_type'); // mortality, growth, efficiency, feed
            $table->string('severity'); // low, medium, high, critical
            $table->string('title');
            $table->text('description');
            $table->json('metrics')->nullable();
            $table->text('recommendation')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('livestock_id')->references('id')->on('livestocks');
            $table->foreign('farm_id')->references('id')->on('farms');
            $table->foreign('coop_id')->references('id')->on('coops');
            $table->foreign('resolved_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->index(['alert_type', 'severity', 'is_resolved']);
            $table->index(['created_at', 'severity']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_alerts');
        Schema::dropIfExists('performance_benchmarks');
        Schema::dropIfExists('period_analytics');
        Schema::dropIfExists('daily_analytics');
    }
};
