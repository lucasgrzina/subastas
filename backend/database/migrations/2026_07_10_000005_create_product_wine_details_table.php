<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_wine_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->foreignId('winery_id')->constrained('wineries')->cascadeOnDelete();
            $table->foreignId('grape_variety_id')->constrained('grape_varieties')->cascadeOnDelete();
            $table->foreignId('wine_region_id')->constrained('wine_regions')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wine_details');
    }
};
