<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->char('order_number', 11)->unique();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('admin_fee')->default(0);
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded'])->default('pending');
            $table->string('duitku_payment_code', 20)->nullable();
            $table->string('duitku_reference', 100)->nullable()->index();
            $table->string('payment_url', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('paid_note', 200)->nullable();
            $table->timestamp('purchase_tracked_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refunded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('refund_note', 200)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
