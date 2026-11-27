<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('youtube_url', 255)->nullable()->after('content');
            $table->boolean('requires_signup')->default(true)->after('youtube_url');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('signup_landing', 130)->nullable()->after('signup_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signup_landing');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['youtube_url', 'requires_signup']);
        });
    }
};
