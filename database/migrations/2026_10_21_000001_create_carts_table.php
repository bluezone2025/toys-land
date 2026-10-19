<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('items')->nullable();
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('subtotal', 10, 3)->default(0);
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->index(['items_count', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
