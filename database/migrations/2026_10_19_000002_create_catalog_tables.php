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
            $table->string('slug')->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('desc_ar')->nullable();
            $table->string('desc_en')->nullable();
            $table->string('badge_ar')->nullable();
            $table->string('badge_en')->nullable();
            $table->string('image')->nullable();
            $table->string('home_group')->nullable(); // edu | fun
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('age_groups', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 1-3, 3-5 ...
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('desc_ar')->nullable();
            $table->string('desc_en')->nullable();
            $table->string('color')->default('sky');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('age_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('desc_ar')->nullable();
            $table->text('desc_en')->nullable();
            $table->string('badge_ar')->nullable();
            $table->string('badge_en')->nullable();
            $table->decimal('price', 10, 3);
            $table->decimal('compare_price', 10, 3)->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('has_options')->default(true);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('sales_count')->default(0);
            $table->timestamps();
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // edition | addon
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('icon')->nullable();
            $table->decimal('price', 10, 3)->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('products');
        Schema::dropIfExists('age_groups');
        Schema::dropIfExists('categories');
    }
};
