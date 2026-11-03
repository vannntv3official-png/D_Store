<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_zone_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('province_id')->index();
            $table->string('district_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->decimal('rate', 12, 4)->default(0.0000);
            $table->decimal('min_order_free_shipping', 12, 4)->nullable();
            $table->string('estimated_delivery_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number')->nullable()->unique();
            $table->string('carrier_name')->nullable();
            $table->enum('status', [
                'pending',
                'picked_up',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'failed',
            ])->default('pending')->index();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_histories');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zone_locations');
        Schema::dropIfExists('shipping_zones');
    }
};
