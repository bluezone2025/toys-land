<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('badge_ar')->nullable();
            $table->string('badge_en')->nullable();
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('desc_ar')->nullable();
            $table->text('desc_en')->nullable();
            $table->string('btn_ar')->nullable();
            $table->string('btn_en')->nullable();
            $table->string('link')->nullable();
            $table->string('image');
            $table->string('theme')->default('slate'); // slate | sky | indigo
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('q_ar');
            $table->string('q_en');
            $table->text('a_ar');
            $table->text('a_en');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('badge_ar')->nullable();
            $table->string('badge_en')->nullable();
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('subtitle_ar')->nullable();
            $table->text('subtitle_en')->nullable();
            $table->string('image')->nullable();
            $table->json('stats')->nullable();
            $table->json('cards')->nullable();
            $table->longText('body_ar')->nullable();
            $table->longText('body_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('hero_slides');
    }
};
