<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('gateway', ['cod', 'bank_transfer', 'khqr_sandbox', 'aba_payway_sandbox'])->index();
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 12, 4);
            $table->enum('status', ['pending', 'authorized', 'paid', 'failed', 'refunded'])->default('pending')->index();
            $table->string('merchant_reference')->unique();
            $table->text('payment_proof_url')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['charge', 'refund'])->default('charge');
            $table->decimal('amount', 12, 4);
            $table->string('status');
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->index();
            $table->string('event_id');
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'ignored', 'failed'])->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
