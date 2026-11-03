<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('lifetime_points_earned')->default(0);
            $table->unsignedInteger('lifetime_points_spent')->default(0);
            $table->unsignedInteger('current_points_balance')->default(0);
            $table->unsignedInteger('points_debt')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->integer('points_change');
            $table->unsignedInteger('resulting_balance');
            $table->unsignedInteger('resulting_debt');
            $table->enum('event_type', [
                'registration',
                'first_order',
                'order_spend',
                'review',
                'redemption',
                'refund_clawback',
                'adjustment',
                'expiry',
            ])->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('event_key')->unique();
            $table->string('signature')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('point_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedInteger('initial_points');
            $table->unsignedInteger('remaining_points');
            $table->timestamp('expires_at')->index();
            $table->boolean('is_expired')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('points_cost');
            $table->enum('discount_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('discount_value', 12, 4);
            $table->decimal('min_spend_amount', 12, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('points_spent');
            $table->unsignedBigInteger('coupon_id')->nullable()->index();
            $table->enum('status', ['completed', 'revoked'])->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('point_lots');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
    }
};
