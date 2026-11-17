<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_config') && ! Schema::hasColumn('app_config', 'metadata')) {
            Schema::table('app_config', function (Blueprint $table) {
                $table->longText('metadata')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('app_config') && Schema::hasColumn('app_config', 'metadata')) {
            Schema::table('app_config', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
