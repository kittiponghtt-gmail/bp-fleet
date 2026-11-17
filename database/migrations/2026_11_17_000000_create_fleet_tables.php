<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->string('id', 25)->primary();
                $table->string('plate', 40)->default('');
                $table->string('status', 20)->default('active');
                $table->string('make', 80)->default('');
                $table->string('model', 80)->default('');
                $table->unsignedSmallInteger('year')->nullable();
                $table->string('vin', 40)->default('');
                $table->string('type', 80)->default('');
                $table->string('driver', 120)->default('');
                $table->string('branch', 120)->default('');
                $table->string('department', 120)->default('');
                $table->date('insurance_expiry')->nullable();
                $table->date('tax_expiry')->nullable();
                $table->date('registration_expiry')->nullable();
                $table->date('inspection_expiry')->nullable();
                $table->unsignedInteger('current_odometer')->nullable();
                $table->date('odometer_date')->nullable();
                $table->date('engine_service_date')->nullable();
                $table->unsignedInteger('engine_service_odometer')->nullable();
                $table->date('major_service_date')->nullable();
                $table->unsignedInteger('major_service_odometer')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('source_row')->nullable();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('rules')) {
            Schema::create('rules', function (Blueprint $table) {
                $table->string('id', 40)->primary();
                $table->string('category', 60)->unique();
                $table->unsignedInteger('km_interval')->nullable();
                $table->unsignedInteger('days_interval')->nullable();
                $table->unsignedInteger('alert_km')->default(0);
                $table->unsignedInteger('alert_days')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->string('id', 40)->primary();
                $table->string('vehicle_id', 25);
                $table->string('category', 60);
                $table->date('service_date');
                $table->unsignedInteger('odometer')->nullable();
                $table->decimal('cost', 12, 2)->nullable();
                $table->string('vendor', 160)->default('');
                $table->text('description')->nullable();
                $table->string('receipt', 120)->default('');
                $table->unsignedInteger('downtime_days')->default(0);
                $table->boolean('verified')->default(true);
                $table->string('source', 40)->default('Manual');
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->index(['vehicle_id', 'service_date']);
                $table->foreign('vehicle_id')->references('id')->on('vehicles');
            });
        }

        if (! Schema::hasTable('odometer_logs')) {
            Schema::create('odometer_logs', function (Blueprint $table) {
                $table->string('id', 40)->primary();
                $table->string('vehicle_id', 25);
                $table->date('reading_date');
                $table->unsignedInteger('odometer');
                $table->text('notes')->nullable();
                $table->string('source', 40)->default('Manual');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['vehicle_id', 'reading_date']);
                $table->foreign('vehicle_id')->references('id')->on('vehicles');
            });
        }

        if (! Schema::hasTable('downtime')) {
            Schema::create('downtime', function (Blueprint $table) {
                $table->string('id', 40)->primary();
                $table->string('vehicle_id', 25);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('reason', 80)->default('');
                $table->text('description')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['vehicle_id', 'start_date', 'end_date']);
                $table->foreign('vehicle_id')->references('id')->on('vehicles');
            });
        }

        if (! Schema::hasTable('issues')) {
            Schema::create('issues', function (Blueprint $table) {
                $table->increments('id');
                $table->string('vehicle_id', 25)->nullable();
                $table->string('field_name', 80);
                $table->string('issue');
                $table->string('value_text')->default('');
                $table->boolean('resolved')->default(false);
                $table->index('vehicle_id');
                $table->foreign('vehicle_id')->references('id')->on('vehicles');
            });
        }

        if (! Schema::hasTable('app_config')) {
            Schema::create('app_config', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->date('tracking_start')->nullable();
            });
            DB::table('app_config')->insert(['id' => 1, 'tracking_start' => null]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
        Schema::dropIfExists('downtime');
        Schema::dropIfExists('odometer_logs');
        Schema::dropIfExists('services');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('app_config');
    }
};
