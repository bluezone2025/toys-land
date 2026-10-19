<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('path', 500);
            $table->string('route')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('referrer')->nullable();
            $table->string('device', 20)->nullable();   // desktop | mobile | tablet
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('locale', 2)->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index(); // add_to_cart | begin_checkout | purchase | search | wishlist
            $table->string('session_id', 100)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('value', 10, 3)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('visits');
    }
};
