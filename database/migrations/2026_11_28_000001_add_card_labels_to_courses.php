<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('badge', 30)->nullable()->after('level');
            $table->decimal('rating', 2, 1)->nullable()->after('badge');
            $table->string('fish_type', 40)->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['badge', 'rating', 'fish_type']);
        });
    }
};
