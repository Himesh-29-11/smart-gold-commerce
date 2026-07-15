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
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();
            $table->decimal('interest_rate_min', 6, 2)->nullable();
            $table->decimal('interest_rate_max', 6, 2)->nullable();
            $table->unsignedSmallInteger('tenure_min_months')->nullable();
            $table->unsignedSmallInteger('tenure_max_months')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('description');
            $table->string('purity', 10)->index();
            $table->decimal('weight_grams', 10, 3);
            $table->string('certification');
            $table->string('hallmark_number')->nullable();
            $table->string('pricing_mode', 20)->default('live');
            $table->decimal('base_price', 14, 2)->default(0);
            $table->decimal('making_charge', 14, 2)->default(0);
            $table->decimal('gst_percentage', 5, 2)->default(3);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('image_url');
            $table->json('gallery')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['category_id', 'purity', 'is_active']);
        });

        Schema::create('gold_price_histories', function (Blueprint $table) {
            $table->id();
            $table->string('carat', 10)->index();
            $table->decimal('price_per_gram', 14, 2);
            $table->string('currency', 3)->default('INR');
            $table->decimal('market_change', 10, 2)->default(0);
            $table->string('source');
            $table->timestamp('fetched_at')->index();
            $table->timestamps();
            $table->index(['carat', 'fetched_at']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type', 20);
            $table->decimal('value', 14, 2);
            $table->decimal('minimum_order', 14, 2)->default(0);
            $table->decimal('maximum_discount', 14, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('coupon_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('delivery_charge', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('coupon_code')->nullable();
            $table->json('shipping_address');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->json('product_snapshot');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_order_id')->nullable()->index();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('status', 30)->default('initiated')->index();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('INR');
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->decimal('monthly_income', 14, 2);
            $table->string('employment_type', 30);
            $table->decimal('requested_amount', 14, 2);
            $table->unsignedSmallInteger('tenure_months');
            $table->decimal('existing_monthly_emi', 14, 2)->default(0);
            $table->decimal('estimated_emi', 14, 2)->nullable();
            $table->unsignedTinyInteger('eligibility_score')->nullable();
            $table->string('status', 30)->default('submitted')->index();
            $table->string('provider_reference')->nullable()->index();
            $table->timestamp('transmitted_at')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->json('documents')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('is_approved')->default(false)->index();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 30)->default('registration');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('loan_requests');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('gold_price_histories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('categories');
    }
};
