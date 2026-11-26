<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('physician_schedules')->update(['slot_duration' => 60]);

        Schema::table('physician_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('slot_duration')->default(60)->change();
        });
    }

    public function down(): void
    {
        Schema::table('physician_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('slot_duration')->default(30)->change();
        });
    }
};
