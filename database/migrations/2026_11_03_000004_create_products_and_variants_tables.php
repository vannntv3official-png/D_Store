<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('warranty_information')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 12, 4);
            $table->decimal('compare_at_price', 12, 4)->nullable();
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('text_value')->nullable();
            $table->decimal('number_value', 12, 4)->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_definition_id'], 'variant_attr_unique');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_url');
            $table->string('alt_text')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
