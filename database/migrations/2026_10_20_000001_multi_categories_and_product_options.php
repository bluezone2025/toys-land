<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Products can belong to several categories and age groups, and each product has its own
 * editions / add-ons. Existing single links and the shared option list are carried over.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('age_group_product', function (Blueprint $table) {
            $table->foreignId('age_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['age_group_id', 'product_id']);
        });

        Schema::table('product_options', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $products = DB::table('products')->get(['id', 'category_id', 'age_group_id', 'has_options']);
        $shared = DB::table('product_options')->whereNull('product_id')->get();
        $now = now();

        foreach ($products as $p) {
            if ($p->category_id) {
                DB::table('category_product')->insert(['category_id' => $p->category_id, 'product_id' => $p->id]);
            }
            if ($p->age_group_id) {
                DB::table('age_group_product')->insert(['age_group_id' => $p->age_group_id, 'product_id' => $p->id]);
            }
            if ($p->has_options) {
                foreach ($shared as $o) {
                    DB::table('product_options')->insert([
                        'product_id' => $p->id, 'type' => $o->type, 'name_ar' => $o->name_ar, 'name_en' => $o->name_en,
                        'icon' => $o->icon, 'price' => $o->price, 'sort' => $o->sort, 'is_active' => $o->is_active,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
        DB::table('product_options')->whereNull('product_id')->delete();

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('age_group_id');
            $table->dropColumn('has_options');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('age_group_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('has_options')->default(true);
        });
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
        Schema::dropIfExists('age_group_product');
        Schema::dropIfExists('category_product');
    }
};
