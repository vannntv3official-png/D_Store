<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('on_hand_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_change');
            $table->unsignedInteger('resulting_on_hand');
            $table->unsignedInteger('resulting_reserved');
            $table->enum('reason', [
                'initial_stock',
                'restock',
                'adjustment',
                'order_reserved',
                'order_fulfilled',
                'reservation_expired',
                'order_cancelled',
                'return_inspected',
            ]);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operation_reference')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('status', ['reserved', 'consumed', 'released'])->default('reserved')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory');
    }
};
