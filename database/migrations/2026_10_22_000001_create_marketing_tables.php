<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 2)->default('ar');
            $table->timestamp('unsubscribed_at')->nullable();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->timestamp('whatsapp_sent_at')->nullable();
        });

        // Buyers who checked out without an account, one row per email.
        Schema::create('guest_customers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('locale', 2)->default('ar');
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('total_spent', 12, 3)->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject_ar');
            $table->string('subject_en')->nullable();
            $table->longText('body_ar');
            $table->longText('body_en')->nullable();
            $table->string('button_text_ar')->nullable();
            $table->string('button_text_en')->nullable();
            $table->string('button_url')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('include_guests')->default(false);
            $table->unsignedInteger('repeat_hours')->nullable(); // 48 = every two days, null = manual only
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('last_run_recipients')->default(0);
            $table->unsignedBigInteger('sent_total')->default(0);
            $table->timestamps();
        });

        // Backfill guest buyers from existing orders.
        DB::table('orders')->whereNull('user_id')->whereNotNull('email')->orderBy('id')->get()
            ->groupBy(fn ($o) => strtolower($o->email))
            ->each(function ($orders, $email) {
                $last = $orders->last();
                DB::table('guest_customers')->insert([
                    'email' => $email, 'name' => $last->name, 'phone' => $last->phone, 'country_code' => $last->country_code,
                    'city' => $last->city, 'address' => 'Block '.$last->block.' - '.$last->apartment, 'locale' => $last->locale,
                    'orders_count' => $orders->count(), 'total_spent' => $orders->where('status', '!=', 'cancelled')->sum('total'),
                    'last_order_at' => $last->created_at, 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('guest_customers');
        Schema::table('carts', fn (Blueprint $table) => $table->dropColumn('whatsapp_sent_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['locale', 'unsubscribed_at']));
    }
};
