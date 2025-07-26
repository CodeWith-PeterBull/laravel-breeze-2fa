<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all the settings for the Two-Factor
    | Authentication package. You can enable/disable features, configure
    | different 2FA methods, and customize the behavior to suit your needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | This option controls whether two-factor authentication is enabled for
    | your application. When disabled, all 2FA functionality will be
    | bypassed, allowing normal authentication to proceed.
    |
    */

    'enabled' => env('TWO_FACTOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Require Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, all users will be required to set up
    | two-factor authentication. Users without 2FA enabled will be
    | redirected to the setup page after authentication.
    |
    */

    'required' => env('TWO_FACTOR_REQUIRED', false),

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Methods
    |--------------------------------------------------------------------------
    |
    | Here you can configure which two-factor authentication methods are
    | available to your users. Each method can be enabled or disabled
    | independently and has its own specific configuration options.
    |
    */

    'methods' => [

        /*
        |----------------------------------------------------------------------
        | Time-based One-Time Password (TOTP)
        |----------------------------------------------------------------------
        |
        | TOTP is the most common 2FA method using authenticator apps like
        | Google Authenticator, Authy, or 1Password. Users scan a QR code
        | to set up their authenticator app.
        |
        */

        'totp' => [
            'enabled' => env('TWO_FACTOR_TOTP_ENABLED', true),
            'issuer' => env('TWO_FACTOR_TOTP_ISSUER', env('APP_NAME', 'Laravel')),
            'algorithm' => env('TWO_FACTOR_TOTP_ALGORITHM', 'sha1'), // sha1, sha256, sha512
            'digits' => env('TWO_FACTOR_TOTP_DIGITS', 6), // 6 or 8
            'period' => env('TWO_FACTOR_TOTP_PERIOD', 30), // seconds
            'window' => env('TWO_FACTOR_TOTP_WINDOW', 1), // time drift tolerance
            'qr_code' => [
                'size' => 200, // QR code size in pixels
                'margin' => 4, // QR code margin
                'error_correction' => 'M', // L, M, Q, H
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Email One-Time Password (Email OTP)
        |----------------------------------------------------------------------
        |
        | Email OTP sends a time-limited code to the user's email address.
        | This is useful as a backup method or for users who prefer not
        | to use authenticator apps.
        |
        */

        'email' => [
            'enabled' => env('TWO_FACTOR_EMAIL_ENABLED', true),
            'expiry' => env('TWO_FACTOR_EMAIL_EXPIRY', 300), // seconds (5 minutes)
            'length' => env('TWO_FACTOR_EMAIL_LENGTH', 6), // code length
            'template' => 'two-factor::emails.otp', // email template
            'subject' => 'Your verification code', // email subject
            'queue' => env('TWO_FACTOR_EMAIL_QUEUE', null), // queue name
        ],

        /*
        |----------------------------------------------------------------------
        | SMS One-Time Password (SMS OTP)
        |----------------------------------------------------------------------
        |
        | SMS OTP sends a time-limited code to the user's mobile phone.
        | Requires integration with an SMS provider like Twilio or Vonage.
        |
        */

        'sms' => [
            'enabled' => env('TWO_FACTOR_SMS_ENABLED', false),
            'provider' => env('TWO_FACTOR_SMS_PROVIDER', 'twilio'), // twilio, vonage, messagebird
            'expiry' => env('TWO_FACTOR_SMS_EXPIRY', 300), // seconds (5 minutes)
            'length' => env('TWO_FACTOR_SMS_LENGTH', 6), // code length
            'message' => 'Your verification code is: {code}', // SMS message template
            'queue' => env('TWO_FACTOR_SMS_QUEUE', null), // queue name
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different SMS providers. Each provider has its own
    | specific settings and credentials required for sending SMS messages.
    |
    */

    'sms_providers' => [

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_PHONE_NUMBER'),
        ],

        'vonage' => [
            'api_key' => env('VONAGE_API_KEY'),
            'api_secret' => env('VONAGE_API_SECRET'),
            'from' => env('VONAGE_PHONE_NUMBER', 'Laravel'),
        ],

        'messagebird' => [
            'access_key' => env('MESSAGEBIRD_ACCESS_KEY'),
            'originator' => env('MESSAGEBIRD_ORIGINATOR', 'Laravel'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Recovery Codes Configuration
    |--------------------------------------------------------------------------
    |
    | Recovery codes are backup codes that users can use when they don't
    | have access to their primary 2FA method. Each code can only be
    | used once and should be stored securely by the user.
    |
    */

    'recovery_codes' => [
        'enabled' => env('TWO_FACTOR_RECOVERY_CODES_ENABLED', true),
        'count' => env('TWO_FACTOR_RECOVERY_CODES_COUNT', 8), // number of codes to generate
        'length' => env('TWO_FACTOR_RECOVERY_CODES_LENGTH', 10), // length of each code
        'regenerate_threshold' => env('TWO_FACTOR_RECOVERY_CODES_THRESHOLD', 3), // regenerate when this many codes remain
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Device Configuration
    |--------------------------------------------------------------------------
    |
    | This feature allows users to mark devices as "trusted" so they don't
    | need to enter 2FA codes on every login from that device. The device
    | is remembered for the specified duration.
    |
    */

    'remember_device' => [
        'enabled' => env('TWO_FACTOR_REMEMBER_DEVICE_ENABLED', true),
        'duration' => env('TWO_FACTOR_REMEMBER_DEVICE_DURATION', 30 * 24 * 60), // minutes (30 days)
        'name' => env('TWO_FACTOR_REMEMBER_DEVICE_NAME', 'two_factor_remember'), // cookie name
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Rate limiting helps prevent brute force attacks by limiting the number
    | of 2FA attempts a user can make within a specific time window.
    |
    */

    'rate_limiting' => [
        'enabled' => env('TWO_FACTOR_RATE_LIMITING_ENABLED', true),
        'max_attempts' => env('TWO_FACTOR_RATE_LIMITING_MAX_ATTEMPTS', 5), // per user per window
        'decay_minutes' => env('TWO_FACTOR_RATE_LIMITING_DECAY_MINUTES', 15), // rate limit window
        'key_generator' => null, // custom rate limit key generator (callable)
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database tables and connections used by the
    | two-factor authentication system.
    |
    */

    'database' => [
        'connection' => env('TWO_FACTOR_DB_CONNECTION', null), // use default connection if null
        'tables' => [
            'two_factor_auths' => 'two_factor_auths',
            'two_factor_recovery_codes' => 'two_factor_recovery_codes',
            'two_factor_sessions' => 'two_factor_sessions',
            'two_factor_attempts' => 'two_factor_attempts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the routes provided by the two-factor authentication
    | package. You can customize route prefixes, middleware, and names.
    |
    */

    'routes' => [
        'enabled' => env('TWO_FACTOR_ROUTES_ENABLED', true),
        'prefix' => env('TWO_FACTOR_ROUTES_PREFIX', 'two-factor'),
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'two-factor.',
    ],

    /*
    |--------------------------------------------------------------------------
    | View Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the views and UI components provided by the package.
    | You can customize the layout and styling to match your application.
    |
    */

    'views' => [
        'layout' => env('TWO_FACTOR_VIEWS_LAYOUT', 'layouts.app'), // master layout
        'theme' => env('TWO_FACTOR_VIEWS_THEME', 'default'), // default, bootstrap, tailwind
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Additional security settings for the two-factor authentication system.
    |
    */

    'security' => [
        'encrypt_secrets' => env('TWO_FACTOR_ENCRYPT_SECRETS', true), // encrypt TOTP secrets
        'hash_recovery_codes' => env('TWO_FACTOR_HASH_RECOVERY_CODES', true), // hash recovery codes
        'session_timeout' => env('TWO_FACTOR_SESSION_TIMEOUT', 10), // minutes for 2FA session
        'max_recovery_attempts' => env('TWO_FACTOR_MAX_RECOVERY_ATTEMPTS', 3), // max recovery code attempts
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for events fired by the two-factor authentication system.
    | You can disable events if you don't need them for performance reasons.
    |
    */

    'events' => [
        'enabled' => env('TWO_FACTOR_EVENTS_ENABLED', true),
        'listeners' => [
            // Add custom event listeners here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching used by the two-factor authentication system.
    | This includes OTP codes, rate limiting, and device remember tokens.
    |
    */

    'cache' => [
        'store' => env('TWO_FACTOR_CACHE_STORE', null), // use default cache store if null
        'prefix' => env('TWO_FACTOR_CACHE_PREFIX', 'two_factor'),
        'ttl' => [
            'otp_codes' => env('TWO_FACTOR_CACHE_OTP_TTL', 300), // seconds
            'rate_limits' => env('TWO_FACTOR_CACHE_RATE_LIMIT_TTL', 900), // seconds
        ],
    ],

];
