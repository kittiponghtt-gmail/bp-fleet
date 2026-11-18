<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('garages')) {
            Schema::create('garages', function (Blueprint $table) {
                $table->string('id', 40)->primary();
                $table->string('name', 160);
                $table->string('phone', 40)->default('');
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'garage_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('garage_id', 40)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'garage_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('garage_id');
            });
        }

        Schema::dropIfExists('garages');
    }
};
