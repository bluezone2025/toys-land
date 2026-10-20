<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One address shape for checkout, saved addresses and orders (Kuwait-style:
 * area, block, street, avenue, building, floor/flat) plus country dial codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Country list is cached without the new column until refreshed.
        Cache::forget('countries');

        Schema::table('countries', function (Blueprint $table) {
            $table->string('dial_code', 6)->nullable()->after('flag');
        });
        foreach (['KW' => '965', 'SA' => '966', 'AE' => '971', 'QA' => '974', 'BH' => '973', 'OM' => '968'] as $code => $dial) {
            DB::table('countries')->where('code', $code)->update(['dial_code' => $dial]);
        }

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('country_code', 2)->default('KW')->after('title');
            $table->string('block', 50)->nullable()->after('area');
            $table->string('street', 150)->nullable()->after('block');
            $table->string('avenue', 100)->nullable()->after('street');
            $table->string('property_type', 20)->default('house')->after('avenue');
            $table->string('building', 100)->nullable()->after('property_type');
            $table->string('floor', 100)->nullable()->after('building');
            $table->string('details')->nullable()->change();
        });
        DB::table('addresses')->whereNull('street')->update(['street' => DB::raw('details')]);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('street', 150)->nullable()->after('block');
            $table->string('avenue', 100)->nullable()->after('street');
            $table->string('floor', 100)->nullable()->after('apartment');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['street', 'avenue', 'floor']));
        Schema::table('addresses', fn (Blueprint $table) => $table->dropColumn(['country_code', 'block', 'street', 'avenue', 'property_type', 'building', 'floor']));
        Schema::table('countries', fn (Blueprint $table) => $table->dropColumn('dial_code'));
    }
};
