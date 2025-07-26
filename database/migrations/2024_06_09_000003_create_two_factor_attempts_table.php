<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Two Factor Attempts Table
 *
 * This migration creates the table for tracking two-factor authentication
 * attempts. This is used for rate limiting, security monitoring, and
 * audit purposes to prevent brute force attacks.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA
 * @author MetaSoft Developers <info@metasoftdevs.com>
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
        Schema::create('two_factor_attempts', function (Blueprint $table) {
            $table->id();

            // User and session information
            $table->unsignedBigInteger('user_id')->nullable()
                ->comment('User ID if authenticated, null for anonymous attempts');

            $table->string('session_id', 255)->nullable()
                ->comment('Session ID for tracking anonymous attempts');

            // Request information
            $table->string('ip_address', 45)
                ->comment('IP address of the attempt');

            $table->text('user_agent')
                ->comment('User agent string');

            // Attempt details
            $table->enum('method', ['totp', 'email', 'sms', 'recovery'])
                ->comment('2FA method used for this attempt');

            $table->enum('type', ['verification', 'setup', 'challenge'])
                ->comment('Type of 2FA operation attempted');

            $table->boolean('successful')->default(false)
                ->comment('Whether the attempt was successful');

            $table->string('failure_reason', 100)->nullable()
                ->comment('Reason for failure (invalid_code, expired, etc.)');

            // Code information (hashed for security)
            $table->string('code_hash', 255)->nullable()
                ->comment('Hash of the attempted code (for duplicate detection)');

            $table->tinyInteger('code_length')->nullable()
                ->comment('Length of the attempted code');

            // Timing information
            $table->timestamp('attempted_at')->nullable()
                ->comment('When the attempt was made');

            // Standard timestamps
            $table->timestamps();

            // Indexes for performance and rate limiting
            $table->index(['ip_address', 'attempted_at'])
                ->comment('Rate limiting by IP address');

            $table->index(['user_id', 'attempted_at'])
                ->comment('Rate limiting by user');

            $table->index(['session_id', 'attempted_at'])
                ->comment('Rate limiting by session');

            $table->index(['successful', 'attempted_at'])
                ->comment('Find successful/failed attempts');

            $table->index(['method', 'type', 'attempted_at'])
                ->comment('Analytics by method and type');

            $table->index('attempted_at')
                ->comment('Chronological ordering and cleanup');

            // Composite indexes for common queries
            $table->index(['user_id', 'successful', 'attempted_at'])
                ->comment('User success rate analysis');

            $table->index(['ip_address', 'successful', 'attempted_at'])
                ->comment('IP-based security analysis');

            // Foreign key constraint (commented out for flexibility)
            // Uncomment if you want to enforce referential integrity
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_attempts');
    }
};
