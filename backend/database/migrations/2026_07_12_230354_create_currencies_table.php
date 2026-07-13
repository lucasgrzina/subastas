<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('guid')->unique()->comment('UUID generado automáticamente por HasGuid trait');
            $table->string('code');
            $table->string('name');
            $table->string('symbol');
            $table->boolean('is_active');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
