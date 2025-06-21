<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->double('schedule_latitude');
            $table->double('schedule_longitude');
            $table->time('schedule_check_in');
            $table->time('schedule_check_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('schedule_latitude');
            $table->dropColumn('schedule_longitude');
            $table->dropColumn('schedule_check_in');
            $table->dropColumn('schedule_check_out');
        });
    }
};
