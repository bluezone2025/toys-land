<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = ['products', 'categories', 'pages'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('seo_title_ar')->nullable();
                $table->string('seo_title_en')->nullable();
                $table->string('seo_description_ar', 500)->nullable();
                $table->string('seo_description_en', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en']));
        }
    }
};
