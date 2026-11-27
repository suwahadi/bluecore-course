<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('level', 40)->nullable()->after('category_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->mediumText('upsell_content')->nullable()->after('requires_signup');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('upsell_content');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
