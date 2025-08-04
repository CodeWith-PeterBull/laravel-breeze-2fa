<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Two Factor Auths Table
 *
 * This migration creates the main table for storing two-factor authentication
 * settings and secrets for users. It includes support for multiple 2FA methods
 * and proper indexing for performance.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('two_factor_auths', function (Blueprint $table) {
            $table->id();

            // User relationship - using unsignedBigInteger for compatibility
            $table->unsignedBigInteger('user_id');

            // Two-factor authentication settings
            $table->boolean('enabled')->default(false)
                ->comment('Whether 2FA is enabled and confirmed for this user');

            $table->enum('method', ['totp', 'email', 'sms'])
                ->comment('The 2FA method chosen by the user');

            // TOTP secret (encrypted) - used for authenticator apps
            $table->text('secret')->nullable()
                ->comment('Encrypted TOTP secret for authenticator apps');

            // Phone number for SMS (optional, can also be stored on user model)
            $table->string('phone_number', 20)->nullable()
                ->comment('Phone number for SMS-based 2FA');

            // Confirmation tracking
            $table->timestamp('confirmed_at')->nullable()
                ->comment('When the user confirmed their 2FA setup');

            // Recovery codes tracking
            $table->timestamp('backup_codes_generated_at')->nullable()
                ->comment('When backup recovery codes were last generated');

            // Standard timestamps
            $table->timestamps();

            // Soft deletes for data retention and audit purposes
            $table->softDeletes();

            // Indexes for performance
            $table->unique('user_id')
                ->comment('Each user can only have one 2FA configuration');

            $table->index(['enabled', 'method'])
                ->comment('Quick lookup for enabled users by method');

            $table->index('confirmed_at')
                ->comment('Find confirmed vs unconfirmed setups');

            $table->index('created_at')
                ->comment('Chronological ordering');

            // Foreign key constraint (commented out for flexibility)
            // Uncomment if you want to enforce referential integrity
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_auths');
    }
};
