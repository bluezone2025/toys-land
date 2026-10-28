<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hybrid tracking: a long-lived visitor id on every visit and event, per-visitor journey
 * profiles, live abandoned checkouts, and the columns that keep server-side conversions
 * (Meta / TikTok / Snapchat / GA4) deduplicated and sent exactly once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('event_id')->nullable()->after('session_id');
            $table->uuid('visitor_id')->nullable()->index()->after('event_id');
            $table->json('utm_data')->nullable();
            $table->json('client_context')->nullable(); // ip, user agent, pixel cookies, consent at checkout time
            $table->timestamp('confirmed_at')->nullable(); // emails + purchase sent once
            $table->timestamp('tracking_sent_at')->nullable(); // server-side purchase queued
            $table->timestamp('browser_tracked_at')->nullable(); // browser purchase pushed on the invoice page
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->uuid('event_id')->nullable()->index();
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('source', 10)->default('server'); // server | browser
            $table->string('path', 500)->nullable();
            $table->string('dispatch_status', 10)->nullable()->index(); // pending | sent | failed | skipped
            $table->unsignedTinyInteger('dispatch_attempts')->default(0);
            $table->json('capi')->nullable(); // hashed server payload waiting to be sent
            $table->json('delivery')->nullable(); // per platform result
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
        });

        Schema::create('visitor_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('country', 2)->nullable();
            $table->string('device', 20)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('locale', 2)->nullable();
            $table->string('first_source', 100)->nullable();
            $table->string('first_medium', 100)->nullable();
            $table->string('first_campaign', 150)->nullable();
            $table->string('first_referrer')->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('last_source', 100)->nullable();
            $table->string('last_medium', 100)->nullable();
            $table->string('last_campaign', 150)->nullable();
            $table->string('last_session_id', 100)->nullable();
            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('events_count')->default(0);
            $table->unsignedInteger('products_viewed')->default(0);
            $table->decimal('cart_value', 10, 3)->default(0);
            $table->unsignedInteger('cart_items')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('total_spent', 10, 3)->default(0);
            $table->unsignedSmallInteger('intent_score')->default(0);
            $table->unsignedTinyInteger('stage')->default(0); // 0 browsing … 4 customer
            $table->string('last_event', 40)->nullable();
            $table->string('last_path', 500)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
            $table->index(['stage', 'last_seen_at']);
        });

        Schema::create('abandoned_checkouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('session_id', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('event_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('currency', 3)->default('KWD');
            $table->json('items')->nullable(); // snapshot shown in the admin
            $table->json('cart')->nullable(); // raw cart rows used to restore it
            $table->json('form')->nullable(); // address fields used to refill the checkout
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('subtotal', 10, 3)->default(0); // KWD
            $table->string('coupon_code')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->boolean('gift_wrap')->default(false);
            $table->text('gift_message')->nullable();
            $table->string('step', 20)->default('contact'); // contact | address | payment
            $table->json('utm_data')->nullable();
            $table->string('status', 20)->default('open')->index(); // open | contacted | recovered | converted
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });

        DB::table('analytics_events')->where('type', 'wishlist')->update(['type' => 'add_to_wishlist']);
        // Orders placed before this release already sent their emails; never replay them.
        DB::table('orders')->update(['confirmed_at' => DB::raw('created_at'), 'tracking_sent_at' => DB::raw('created_at'), 'browser_tracked_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_checkouts');
        Schema::dropIfExists('visitor_profiles');
        Schema::table('visits', fn (Blueprint $table) => $table->dropColumn(['visitor_id', 'utm_source', 'utm_medium', 'utm_campaign']));
        Schema::table('analytics_events', fn (Blueprint $table) => $table->dropColumn(['event_id', 'visitor_id', 'source', 'path', 'dispatch_status', 'dispatch_attempts', 'capi', 'delivery']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['event_id', 'visitor_id', 'utm_data', 'client_context', 'confirmed_at', 'tracking_sent_at', 'browser_tracked_at']));
        DB::table('analytics_events')->where('type', 'add_to_wishlist')->update(['type' => 'wishlist']);
    }
};
