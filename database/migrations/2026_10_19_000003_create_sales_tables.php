<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('flag')->nullable();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('symbol_ar');
            $table->string('symbol_en');
            $table->decimal('rate', 12, 4)->default(1);
            $table->decimal('shipping_fee', 10, 3)->default(0); // KWD
            $table->string('delivery_ar')->nullable();
            $table->string('delivery_en')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent'); // percent | fixed
            $table->decimal('value', 10, 3);
            $table->decimal('min_subtotal', 10, 3)->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('area');
            $table->string('details');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('country_code', 2);
            $table->string('city');
            $table->string('block');
            $table->string('property_type');
            $table->string('apartment');
            $table->text('notes')->nullable();
            $table->string('payment_method'); // knet | card | applepay | cod
            $table->string('payment_status')->default('pending'); // pending | paid | failed
            $table->string('status')->default('pending'); // pending | processing | shipped | delivered | cancelled
            $table->decimal('subtotal', 10, 3);
            $table->decimal('discount', 10, 3)->default(0);
            $table->decimal('shipping', 10, 3)->default(0);
            $table->decimal('total', 10, 3);
            $table->string('coupon_code')->nullable();
            $table->string('currency_code', 2)->default('KW');
            $table->decimal('currency_rate', 12, 4)->default(1);
            $table->string('locale', 2)->default('ar');
            $table->string('payment_invoice_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('image')->nullable();
            $table->string('options_ar')->nullable();
            $table->string('options_en')->nullable();
            $table->decimal('unit_price', 10, 3);
            $table->unsignedInteger('qty');
            $table->decimal('total', 10, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('countries');
    }
};
