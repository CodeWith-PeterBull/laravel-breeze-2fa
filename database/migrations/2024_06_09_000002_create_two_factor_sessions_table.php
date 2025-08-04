<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Two Factor Sessions Table
 *
 * This migration creates the table for storing remembered device sessions.
 * When users choose to "remember this device", a secure token is generated
 * and stored to skip 2FA on future logins from the same device.
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
        Schema::create('two_factor_sessions', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->unsignedBigInteger('user_id');

            // Unique token for this remembered device
            $table->string('token', 255)
                ->comment('Secure token for device identification');

            // Device information for identification and security
            $table->string('device_name', 255)->nullable()
                ->comment('Human-readable device name');

            $table->string('ip_address', 45)
                ->comment('IP address when device was remembered');

            $table->text('user_agent')
                ->comment('User agent string for device fingerprinting');

            $table->string('device_fingerprint', 255)->nullable()
                ->comment('Additional device fingerprint data');

            // Session management
            $table->timestamp('expires_at')->nullable()
                ->comment('When this remembered device session expires');

            $table->timestamp('last_used_at')->nullable()
                ->comment('Last time this device was used for 2FA bypass');

            // Standard timestamps
            $table->timestamps();

            // Indexes for performance
            $table->unique(['user_id', 'token'])
                ->comment('Each user can have unique tokens');

            $table->index('token')
                ->comment('Quick token lookup');

            $table->index('expires_at')
                ->comment('Find expired sessions for cleanup');

            $table->index(['user_id', 'expires_at'])
                ->comment('Find active sessions for a user');

            $table->index('last_used_at')
                ->comment('Find inactive sessions');

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
        Schema::dropIfExists('two_factor_sessions');
    }
};
