<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->json('name');
            $table->enum('type', ['text', 'number', 'boolean', 'select', 'multiselect']);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false)->index();
            $table->boolean('is_variant_defining')->default(false)->index();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->json('label');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_definition_id', 'value']);
        });

        Schema::create('category_attribute', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);
            $table->primary(['category_id', 'attribute_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attribute');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_definitions');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
