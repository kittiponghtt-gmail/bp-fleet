<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles') && ! Schema::hasColumn('vehicles', 'code')) {
            Schema::table('vehicles', function (Blueprint $table) {
                // Display label (e.g. CA15). Not unique, editable; `id` stays the stable internal key.
                $table->string('code', 60)->default('')->index();
            });
            DB::table('vehicles')->update(['code' => DB::raw('id')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicles', 'code')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
