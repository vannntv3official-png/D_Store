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
            $table->string('order_number')->unique();
            $table->string('guest_token', 64)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->index();
            $table->string('customer_email')->nullable()->index();
            $table->string('base_currency', 3)->default('USD');
            $table->string('display_currency', 3)->default('KHR');
            $table->decimal('exchange_rate', 12, 4)->default(4100.0000);
            $table->decimal('subtotal', 12, 4);
            $table->decimal('discount_total', 12, 4)->default(0.0000);
            $table->decimal('tax_total', 12, 4)->default(0.0000);
            $table->decimal('shipping_total', 12, 4)->default(0.0000);
            $table->decimal('grand_total', 12, 4);
            $table->decimal('settlement_amount', 12, 4);
            $table->enum('fulfillment_status', [
                'pending',
                'confirmed',
                'processing',
                'packed',
                'shipped',
                'delivered',
                'cancelled',
            ])->default('pending')->index();
            $table->enum('payment_status', [
                'pending',
                'authorized',
                'paid',
                'partially_refunded',
                'refunded',
                'failed',
            ])->default('pending')->index();
            $table->string('shipping_method_name')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->json('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->decimal('unit_price', 12, 4);
            $table->unsignedInteger('quantity');
            $table->decimal('line_subtotal', 12, 4);
            $table->decimal('allocated_discount', 12, 4)->default(0.0000);
            $table->decimal('line_total', 12, 4);
            $table->timestamps();
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['shipping', 'billing'])->default('shipping');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('province_name');
            $table->string('district_name');
            $table->string('address_line');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->enum('type', ['fulfillment', 'payment'])->default('fulfillment');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();
        });

        Schema::create('checkout_idempotencies', function (Blueprint $table) {
            $table->id();
            $table->string('customer_scope')->index();
            $table->string('idempotency_key');
            $table->string('request_hash', 64);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->unique(['customer_scope', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_idempotencies');
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
