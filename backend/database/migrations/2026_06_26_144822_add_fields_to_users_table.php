<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('guid')->unique()->index();
            $table->string('first_name', 50)->after('name');
            $table->string('last_name', 50)->after('first_name');
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->string('verification_code', 6)->nullable()->comment('Código de 6 dígitos para verificar email');
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->string('verification_link_token', 64)->nullable();
            $table->timestamp('verification_link_expires_at')->nullable();
            $table->timestamp('last_verification_email_sent_at')->nullable()->comment('Controla cooldown de reenvío');            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'guid',
                'first_name',
                'last_name',
                'failed_login_attempts',
                'locked_at',
                'last_login_at',
                'password_changed_at',
                'verification_code',
                'verification_code_expires_at',
                'verification_link_token',
                'verification_link_expires_at',
                'last_verification_email_sent_at'
            ]);
        });
    }
};
