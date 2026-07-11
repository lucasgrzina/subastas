<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->uuid('guid')->unique()->comment('UUID generado automáticamente por HasGuid trait');
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->string('lot_number', 50);
            $table->decimal('starting_price', 12, 2);
            $table->decimal('bid_increment', 12, 2);
            $table->decimal('reserve_price', 12, 2)->nullable();
            // Final status set (4 values only): scheduled/open/sold/unsold — no "closed",
            // per tasks obs#41 1.2a. close() only ever produces sold/unsold; the generic
            // update() guard blocks direct writes to either terminal status.
            $table->enum('status', ['scheduled', 'open', 'sold', 'unsold'])->default('scheduled');
            $table->decimal('current_price', 12, 2)->nullable();
            // Denormalized cache pointer, no DB-level FK to bids.id (see design:
            // "Decision: current_bid_id is unconstrained") — avoids a circular
            // migration dependency between lots and bids.
            $table->unsignedBigInteger('current_bid_id')->nullable();
            $table->foreignId('current_winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
