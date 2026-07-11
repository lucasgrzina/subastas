<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->uuid('guid')->unique()->comment('UUID generado automáticamente por HasGuid trait');
            // restrictOnDelete (not cascade) — append-only ledger, schema should not
            // silently permit destroying bid history by deleting a lot.
            $table->foreignId('lot_id')->constrained('lots')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 12, 2);
            // Bids are append-only (no updated_at) — see Bid model immutability guards.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
