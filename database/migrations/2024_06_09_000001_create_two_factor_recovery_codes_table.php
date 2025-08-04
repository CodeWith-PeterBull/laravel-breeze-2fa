<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Two Factor Recovery Codes Table
 *
 * This migration creates the table for storing recovery codes that users
 * can use as backup when they don't have access to their primary 2FA method.
 * Each code is hashed and can only be used once.
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
        Schema::create('two_factor_recovery_codes', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->unsignedBigInteger('user_id');

            // Hashed recovery code for security
            $table->string('code_hash', 255)
                ->comment('Hashed recovery code for secure storage');

            // Usage tracking
            $table->timestamp('used_at')->nullable()
                ->comment('When this recovery code was used');

            $table->string('used_ip', 45)->nullable()
                ->comment('IP address where the code was used');

            $table->text('used_user_agent')->nullable()
                ->comment('User agent string when code was used');

            // Standard timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id')
                ->comment('Find codes for a specific user');

            $table->index(['user_id', 'used_at'])
                ->comment('Find unused codes for a user');

            $table->index('used_at')
                ->comment('Find used/unused codes globally');

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
        Schema::dropIfExists('two_factor_recovery_codes');
    }
};
