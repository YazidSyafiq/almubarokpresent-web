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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->foreignId('office_id')->default(1)->constrained('offices')->cascadeOnDelete();
            $table->string('image_url')->nullable();
            $table->text('face_embedding')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('group_id');
            $table->dropColumn('office_id');
            $table->dropColumn('phone');
            $table->dropColumn('is_banned');
            $table->dropColumn('image_url');
            $table->dropColumn('face_embedding');
        });
    }
};
