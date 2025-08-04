# Laravel Breeze 2FA Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/metasoftdevs/laravel-breeze-2fa.svg?style=flat-square)](https://packagist.org/packages/metasoftdevs/laravel-breeze-2fa)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/metasoftdevs/laravel-breeze-2fa/run-tests?label=tests)](https://github.com/metasoftdevs/laravel-breeze-2fa/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/metasoftdevs/laravel-breeze-2fa/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/metasoftdevs/laravel-breeze-2fa/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/metasoftdevs/laravel-breeze-2fa.svg?style=flat-square)](https://packagist.org/packages/metasoftdevs/laravel-breeze-2fa)

An advanced, highly customizable two-factor authentication (2FA) package for Laravel Breeze that supports multiple authentication methods and seamlessly integrates with both Breeze and custom authentication systems.

## ✨ Features

- **🔐 Multiple 2FA Methods**: TOTP (Authenticator Apps), Email OTP, SMS OTP
- **🔑 Recovery Codes**: Secure backup codes for account recovery
- **📱 Device Remembering**: Optional "trust this device" functionality
- **🛡️ Security First**: Rate limiting, encryption, CSRF protection
- **🎨 Laravel Breeze Integration**: Drop-in compatibility with Breeze
- **🔧 Highly Customizable**: Extensive configuration options
- **📋 Custom Auth Support**: Works with any Laravel authentication guard
- **🧪 Fully Tested**: Comprehensive test suite with 90%+ coverage
- **📚 Well Documented**: Extensive documentation and examples

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Laravel Breeze (for seamless integration)

## 🚀 Quick Start

### Installation

Install the package via Composer:

```bash
composer require metasoftdevs/laravel-breeze-2fa
```

### Basic Setup

1. **Publish and run migrations:**

```bash
php artisan vendor:publish --provider="MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider" --tag="two-factor-migrations"
php artisan migrate
```

2. **Publish configuration (optional):**

```bash
php artisan vendor:publish --provider="MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider" --tag="two-factor-config"
```

3. **Install the package setup:**

```bash
php artisan two-factor:install
```

### Laravel Breeze Integration

If you're using Laravel Breeze, the package will automatically integrate with your existing authentication flow:

```php
// In your login controller or middleware
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;

// After successful password authentication
if (TwoFactor::isEnabledForUser($user)) {
    if (!TwoFactor::isDeviceRemembered($user)) {
        // Redirect to 2FA challenge
        return redirect()->route('two-factor.challenge');
    }
}
```

## 📖 Usage Examples

### Enabling 2FA for a User

```php
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;

// Enable TOTP (Authenticator App)
$setup = TwoFactor::enable($user, 'totp');
$qrCodeUrl = $setup['qr_code_url'];
$secret = $setup['secret'];
$recoveryCodes = $setup['recovery_codes'];

// Enable Email OTP
$setup = TwoFactor::enable($user, 'email');
// A verification email will be sent automatically

// Enable SMS OTP
$setup = TwoFactor::enable($user, 'sms');
// A verification SMS will be sent automatically
```

### Confirming 2FA Setup

```php
// User enters the code from their authenticator app/email/SMS
$isConfirmed = TwoFactor::confirm($user, $userProvidedCode);

if ($isConfirmed) {
    // 2FA is now active for the user
    return redirect()->route('dashboard')->with('success', '2FA enabled successfully!');
}
```

### Verifying 2FA During Login

```php
// In your authentication flow
try {
    $verified = TwoFactor::verify($user, $code, $rememberDevice = true);

    if ($verified) {
        // User is authenticated, proceed with login
        Auth::login($user);
        return redirect()->intended('dashboard');
    }
} catch (\MetaSoftDevs\LaravelBreeze2FA\Exceptions\InvalidCodeException $e) {
    return back()->withErrors(['code' => 'Invalid verification code']);
} catch (\MetaSoftDevs\LaravelBreeze2FA\Exceptions\RateLimitExceededException $e) {
    return back()->withErrors(['code' => 'Too many attempts. Please try again later.']);
}
```

### Disabling 2FA

```php
$disabled = TwoFactor::disable($user);

if ($disabled) {
    return redirect()->back()->with('success', '2FA has been disabled.');
}
```

### Getting User's 2FA Status

```php
$status = TwoFactor::getStatus($user);

/*
Returns:
[
    'enabled' => true,
    'method' => 'totp',
    'confirmed' => true,
    'recovery_codes_count' => 6,
    'can_generate_recovery_codes' => true
]
*/
```

## 🔧 Configuration

The package offers extensive configuration options. Publish the config file to customize:

```bash
php artisan vendor:publish --provider="MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider" --tag="two-factor-config"
```

### Key Configuration Options

```php
// config/two-factor.php

return [
    // Enable/disable the entire 2FA system
    'enabled' => env('TWO_FACTOR_ENABLED', true),

    // Require all users to set up 2FA
    'required' => env('TWO_FACTOR_REQUIRED', false),

    // Configure available methods
    'methods' => [
        'totp' => [
            'enabled' => true,
            'issuer' => env('APP_NAME'),
            'window' => 1, // Time drift tolerance
        ],
        'email' => [
            'enabled' => true,
            'expiry' => 300, // 5 minutes
        ],
        'sms' => [
            'enabled' => false,
            'provider' => 'twilio',
        ],
    ],

    // Recovery codes settings
    'recovery_codes' => [
        'enabled' => true,
        'count' => 8,
        'length' => 10,
    ],

    // Device remembering
    'remember_device' => [
        'enabled' => true,
        'duration' => 30 * 24 * 60, // 30 days
    ],

    // Rate limiting
    'rate_limiting' => [
        'max_attempts' => 5,
        'decay_minutes' => 15,
    ],
];
```

## 🔒 SMS Configuration

For SMS OTP, configure your provider credentials:

### Twilio

```env
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=your_twilio_number
```

### Vonage (Nexmo)

```env
VONAGE_API_KEY=your_api_key
VONAGE_API_SECRET=your_api_secret
VONAGE_PHONE_NUMBER=your_sender_id
```

## 🎨 Frontend Integration

### Blade Templates

The package includes pre-built Blade templates that you can customize:

```bash
php artisan vendor:publish --provider="MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider" --tag="two-factor-views"
```

### Vue.js/Inertia.js

For Vue.js applications using Inertia:

```javascript
// Setup 2FA
const setup2FA = async (method) => {
  const response = await axios.post("/two-factor/enable", { method });

  if (method === "totp") {
    // Show QR code: response.data.qr_code_url
    showQRCode(response.data.qr_code_url);
  }

  // Show recovery codes
  showRecoveryCodes(response.data.recovery_codes);
};

// Verify setup
const confirm2FA = async (code) => {
  await axios.post("/two-factor/confirm", { code });
  // 2FA is now enabled
};
```

### Livewire

For Livewire components:

```php
class TwoFactorSetup extends Component
{
    public $method = 'totp';
    public $code = '';
    public $qrCodeUrl = '';
    public $recoveryCodes = [];

    public function enable()
    {
        $setup = TwoFactor::enable(auth()->user(), $this->method);
        $this->qrCodeUrl = $setup['qr_code_url'] ?? '';
        $this->recoveryCodes = $setup['recovery_codes'] ?? [];
    }

    public function confirm()
    {
        TwoFactor::confirm(auth()->user(), $this->code);
        session()->flash('message', '2FA enabled successfully!');
    }
}
```

## 🛠️ Custom Authentication Integration

For custom authentication systems, implement the 2FA flow manually:

```php
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;

// In your custom login controller
class CustomLoginController extends Controller
{
    public function authenticate(Request $request)
    {
        // Your existing authentication logic
        $user = $this->attemptLogin($request);

        if ($user && TwoFactor::isEnabledForUser($user)) {
            if (!TwoFactor::isDeviceRemembered($user)) {
                // Store user in session for 2FA challenge
                session(['2fa_user_id' => $user->id]);
                return redirect()->route('two-factor.challenge');
            }
        }

        // Complete login
        Auth::login($user);
        return redirect()->intended();
    }

    public function challenge()
    {
        // Show 2FA challenge form
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $userId = session('2fa_user_id');
        $user = User::find($userId);

        $verified = TwoFactor::verify($user, $request->code, $request->boolean('remember'));

        if ($verified) {
            session()->forget('2fa_user_id');
            Auth::login($user);
            return redirect()->intended();
        }

        return back()->withErrors(['code' => 'Invalid code']);
    }
}
```

## 🧪 Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## 📋 Commands

The package includes several Artisan commands:

```bash
# Install the package (publish assets, run migrations)
php artisan two-factor:install

# Generate recovery codes for a user
php artisan two-factor:recovery-codes {user-id}

# Clean up expired sessions and codes
php artisan two-factor:cleanup

# Show 2FA statistics
php artisan two-factor:stats
```

## 🔐 Security Considerations

- **Secrets Encryption**: TOTP secrets are encrypted in the database
- **Rate Limiting**: Prevents brute force attacks on 2FA codes
- **Recovery Codes**: Securely hashed and single-use
- **Device Tokens**: Cryptographically secure device remembering
- **Audit Trail**: All authentication attempts are logged
- **CSRF Protection**: All forms include CSRF tokens

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Check code style: `composer format`

## 📜 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## 🛡️ Security

If you discover any security-related issues, please emailinfo@metasoftdevs.com instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Meta Software Developers](https://github.com/metasoftdevs)
- [All Contributors](../../contributors)

## 🔗 Related Packages

- [Laravel Breeze](https://github.com/laravel/breeze) - Simple authentication scaffolding
- [Laravel Fortify](https://github.com/laravel/fortify) - Backend authentication services
- [pragmarx/google2fa](https://github.com/antonioribeiro/google2fa) - Google2FA for Laravel

---

<p align="center">
    <strong>Built with ❤️ by <a href="https://metasoftdevs.com" target="_blank" rel="noopener">Meta Software Developers</a></strong><br>
    <a href="mailto:info@metasoftdevs.com">info@metasoftdevs.com</a>
</p>
