<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('path', 500);
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });

        // Postgres partial unique index: only one is_main=true image per product.
        // This is the final invariant guard — app-level validation checks it first
        // to return a friendly 422, but the DB constraint is authoritative.
        DB::statement(
            'CREATE UNIQUE INDEX product_images_one_main_per_product ON product_images (product_id) WHERE is_main = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
