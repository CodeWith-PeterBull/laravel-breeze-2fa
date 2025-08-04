# Changelog

All notable changes to `metasoftdevs/laravel-breeze-2fa` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial package development
- Complete two-factor authentication system
- Multi-channel support (TOTP, Email OTP, SMS OTP)
- Recovery codes functionality
- Device remembering capability
- Comprehensive test suite
- CI/CD pipeline
- Documentation

## [1.0.0] - 2024-01-15

### Added

- **Core Features**
  - Time-based One-Time Password (TOTP) support with QR codes
  - Email OTP with customizable templates
  - SMS OTP with multiple provider support (Twilio, Vonage, MessageBird)
  - Recovery codes for account recovery
  - Device remembering functionality
- **Security Features**
  - Rate limiting for brute force protection
  - Encrypted storage of sensitive data
  - Device fingerprinting
  - Audit trail for authentication attempts
  - CSRF protection on all forms
- **Laravel Integration**
  - Seamless Laravel Breeze integration
  - Custom authentication guard support
  - Middleware for route protection
  - Artisan commands for management
  - Event system for extensibility
- **User Interface**
  - Responsive Blade templates
  - Vue.js/Inertia.js compatibility
  - Livewire component support
  - Customizable views and layouts
  - Multi-language support
- **Developer Experience**
  - Comprehensive configuration options
  - Extensive documentation
  - Test suite with 90%+ coverage
  - PHP 8.1+ and Laravel 10+ support
  - PSR-12 coding standards
- **Management Features**
  - Admin dashboard for user management
  - Device management for users
  - Recovery code generation and regeneration
  - Analytics and reporting
  - Automatic cleanup commands

### Technical Details

- **Models**: TwoFactorAuth, TwoFactorRecoveryCode, TwoFactorSession, TwoFactorAttempt
- **Services**: TwoFactorManager, TOTPService, EmailOTPService, SMSOTPService, RecoveryCodeService, DeviceRememberService
- **Controllers**: TwoFactorController, TwoFactorRecoveryController, TwoFactorDeviceController
- **Middleware**: RequiresTwoFactor, TwoFactorSetup, RateLimitTwoFactor
- **Events**: TwoFactorEnabled, TwoFactorDisabled, TwoFactorAuthenticated, RecoveryCodeUsed, DeviceRemembered
- **Commands**: InstallTwoFactorCommand, CleanupExpiredSessionsCommand, GenerateRecoveryCodesCommand

### Supported Platforms

- **PHP**: 8.1, 8.2, 8.3
- **Laravel**: 10.x, 11.x, 12.x
- **Databases**: MySQL 5.7+, PostgreSQL 10+, SQLite 3.8+, SQL Server 2017+

### SMS Providers

- Twilio
- Vonage (Nexmo)
- MessageBird

### Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Dependencies

- `pragmarx/google2fa`: TOTP implementation
- `bacon/bacon-qr-code`: QR code generation
- Laravel Framework 10+
- PHP 8.1+

---

## Release Process

### Version Numbering

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR** version when you make incompatible API changes
- **MINOR** version when you add functionality in a backwards compatible manner
- **PATCH** version when you make backwards compatible bug fixes

### Release Types

#### Major Releases (x.0.0)

- Breaking changes
- New major features
- Laravel version updates
- PHP version requirement changes

#### Minor Releases (x.y.0)

- New features
- New SMS providers
- Enhanced functionality
- Performance improvements
- New language translations

#### Patch Releases (x.y.z)

- Bug fixes
- Security patches
- Documentation updates
- Minor improvements

### Development Roadmap

#### Version 1.1.0 (Planned)

- WebAuthn/FIDO2 support
- Enhanced admin dashboard
- Advanced analytics
- Push notifications for 2FA codes
- Backup and restore functionality

#### Version 1.2.0 (Planned)

- Multi-tenant support
- Custom 2FA method plugins
- Advanced device fingerprinting
- API rate limiting improvements
- Enhanced monitoring and alerting

#### Version 2.0.0 (Future)

- Laravel 13 compatibility
- Breaking changes for improved architecture
- Enhanced performance optimizations
- New authentication methods
- Modernized UI components

### Security Updates

Security vulnerabilities will be addressed immediately with patch releases.
If you discover a security vulnerability, please emailinfo@metasoftdevs.com.

### Deprecation Policy

Features will be deprecated for at least one major version before removal.
Deprecated features will be clearly marked in documentation and release notes.

### Upgrade Guides

Detailed upgrade guides will be provided for all major and minor releases
that include breaking changes or significant new features.

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Types of Contributions

- Bug reports and fixes
- Feature requests and implementations
- Documentation improvements
- Translation updates
- Test coverage improvements
- Performance optimizations

### Release Notes Guidelines

When contributing, please update this changelog with your changes under the "Unreleased" section.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE.md).
