<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('merchant_order_id', 64)->index();
            $table->string('duitku_reference', 100)->nullable();
            $table->char('result_code', 2);
            $table->unsignedInteger('amount');
            $table->char('event_key', 64)->nullable()->unique();
            $table->boolean('signature_valid');
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'ignored', 'rejected', 'failed'])->default('received')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('last_error', 255)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callbacks');
    }
};
