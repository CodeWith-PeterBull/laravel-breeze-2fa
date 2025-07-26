<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

/**
 * Test User Model
 *
 * This is a simple user model used for testing the two-factor authentication
 * package. It provides the basic user functionality needed for 2FA testing.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Tests\Fixtures
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return 'users';
    }

    /**
     * Create the users table for testing.
     *
     * @return void
     */
    public static function createTable(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('phone_number')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }
}
