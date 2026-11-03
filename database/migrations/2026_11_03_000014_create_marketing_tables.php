<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('subtitle')->nullable();
            $table->string('link_url')->nullable();
            $table->string('image_url');
            $table->json('button_text')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed', 'override_price'])->default('percentage');
            $table->decimal('discount_value', 12, 4);
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->boolean('is_flash_sale')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('promotional_price', 12, 4)->nullable();
            $table->unsignedInteger('flash_sale_quota')->nullable();
            $table->unsignedInteger('flash_sale_sold')->default(0);
            $table->timestamps();

            $table->unique(['campaign_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_products');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('banners');
    }
};
