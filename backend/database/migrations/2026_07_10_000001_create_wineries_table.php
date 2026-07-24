<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wineries', function (Blueprint $table) {
            $table->id();
            $table->uuid('guid')->unique()->comment('UUID generado automáticamente por HasGuid trait');
            $table->string('name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wineries');
    }
};
