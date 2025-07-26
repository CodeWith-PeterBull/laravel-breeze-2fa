# Contributing to Laravel Breeze 2FA

Thank you for considering contributing to Laravel Breeze 2FA! This guide will help you understand how to contribute effectively to this project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Testing](#testing)
- [Code Style](#code-style)
- [Submitting Changes](#submitting-changes)
- [Release Process](#release-process)

## Code of Conduct

This project adheres to a Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to developers@metasoft.dev.

### Our Standards

- **Be respectful**: Treat everyone with respect and kindness
- **Be inclusive**: Welcome newcomers and encourage diverse perspectives
- **Be collaborative**: Work together and help each other
- **Be constructive**: Provide helpful feedback and suggestions
- **Be patient**: Remember that everyone is learning

## Getting Started

### Types of Contributions

We welcome various types of contributions:

- 🐛 **Bug Reports**: Help us identify and fix issues
- ✨ **Feature Requests**: Suggest new functionality
- 🔧 **Bug Fixes**: Submit fixes for identified issues
- 🚀 **Feature Implementations**: Build new features
- 📖 **Documentation**: Improve or expand documentation
- 🌍 **Translations**: Add or update language files
- 🧪 **Tests**: Improve test coverage
- ⚡ **Performance**: Optimize code for better performance

### Before You Start

1. **Check existing issues**: Look for existing issues or discussions
2. **Read the documentation**: Familiarize yourself with the package
3. **Understand the architecture**: Review the codebase structure
4. **Test the package**: Try the package in a test Laravel application

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 10+ test application
- Git

### Local Development

1. **Fork the repository**

   ```bash
   # Fork on GitHub, then clone your fork
   git clone https://github.com/your-username/laravel-breeze-2fa.git
   cd laravel-breeze-2fa
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Set up testing environment**

   ```bash
   # Copy environment file
   cp .env.example .env

   # Run tests to ensure everything works
   composer test
   ```

4. **Create a test Laravel application**

   ```bash
   # In a separate directory
   composer create-project laravel/laravel test-app
   cd test-app
   composer require laravel/breeze

   # Add your local package
   # Edit composer.json to include:
   # "repositories": [
   #     {
   #         "type": "path",
   #         "url": "../laravel-breeze-2fa"
   #     }
   # ]

   composer require metasoftdevs/laravel-breeze-2fa:@dev
   ```

### Development Workflow

1. **Create a branch**

   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-number
   ```

2. **Make your changes**

   - Write clean, well-documented code
   - Follow existing code patterns
   - Add tests for new functionality
   - Update documentation as needed

3. **Test your changes**

   ```bash
   composer test
   composer analyse
   composer format
   ```

4. **Commit your changes**
   ```bash
   git add .
   git commit -m "feat: add new feature description"
   ```

## Contributing Guidelines

### Code Organization

The package follows a clean architecture with these main components:

```
src/
├── Console/Commands/          # Artisan commands
├── Contracts/                 # Interfaces
├── Events/                    # Event classes
├── Exceptions/                # Custom exceptions
├── Facades/                   # Laravel facades
├── Http/
│   ├── Controllers/           # Controllers
│   ├── Middleware/            # Middleware
│   └── Requests/              # Form requests
├── Mail/                      # Mail classes
├── Models/                    # Eloquent models
├── Services/                  # Business logic services
└── TwoFactorServiceProvider.php
```

### Coding Standards

- **PSR-12**: Follow PSR-12 coding style
- **Type Declarations**: Use strict types and proper type hints
- **Documentation**: Document all public methods and classes
- **Naming**: Use descriptive names for variables, methods, and classes
- **SOLID Principles**: Follow SOLID design principles

### Security Considerations

When contributing security-related features:

- ⚠️ **Never expose sensitive data** in logs or responses
- 🔐 **Always encrypt** sensitive data before storage
- 🛡️ **Validate all inputs** thoroughly
- 🚫 **Prevent timing attacks** in verification functions
- 📝 **Document security implications** of changes

### Performance Guidelines

- 🚀 **Optimize database queries** - avoid N+1 problems
- 💾 **Use caching** appropriately for expensive operations
- 🎯 **Minimize external API calls** and handle failures gracefully
- 📊 **Profile performance** for critical paths
- 🔄 **Use queues** for heavy operations

## Testing

### Test Structure

```
tests/
├── Unit/                      # Unit tests
├── Feature/                   # Feature/integration tests
├── Integration/               # Third-party integration tests
└── Fixtures/                  # Test fixtures and helpers
```

### Writing Tests

1. **Follow AAA pattern**: Arrange, Act, Assert
2. **Test edge cases**: Include boundary conditions and error cases
3. **Mock external dependencies**: Use mocks for external services
4. **Use descriptive names**: Test method names should describe the scenario
5. **Keep tests focused**: One test should verify one specific behavior

### Example Test

```php
/**
 * @test
 */
public function it_enables_totp_for_user_and_generates_recovery_codes(): void
{
    // Arrange
    $user = User::factory()->create();

    // Act
    $setup = TwoFactor::enable($user, 'totp');

    // Assert
    $this->assertArrayHasKey('secret', $setup);
    $this->assertArrayHasKey('qr_code_url', $setup);
    $this->assertArrayHasKey('recovery_codes', $setup);
    $this->assertCount(8, $setup['recovery_codes']);
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/TwoFactorManagerTest.php

# Run tests with filter
vendor/bin/phpunit --filter="test_enables_totp"
```

## Code Style

### Formatting

We use PHP CS Fixer for consistent code formatting:

```bash
# Check code style
composer format-check

# Fix code style
composer format

# Custom rules are in .php-cs-fixer.php
```

### Static Analysis

We use PHPStan for static analysis:

```bash
# Run static analysis
composer analyse

# Configuration is in phpstan.neon
```

### Quality Checks

Run all quality checks before submitting:

```bash
# Run all quality checks
composer quality

# This runs:
# - PHP CS Fixer
# - PHPStan
# - PHPUnit tests
```

## Submitting Changes

### Pull Request Process

1. **Update documentation** if you're changing functionality
2. **Add tests** for new features or bug fixes
3. **Update CHANGELOG.md** under the "Unreleased" section
4. **Ensure all tests pass** and code quality checks succeed
5. **Create a pull request** with a clear title and description

### Pull Request Template

```markdown
## Description

Brief description of changes

## Type of Change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing

- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Manual testing completed

## Checklist

- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Tests pass locally
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
```

### Commit Message Format

Use conventional commits format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Test additions/modifications
- `chore`: Maintenance tasks

Examples:

```
feat(totp): add QR code generation
fix(email): resolve template loading issue
docs(readme): update installation instructions
test(recovery): add recovery code tests
```

## Release Process

### Version Planning

- **Patch releases**: Bug fixes, security patches
- **Minor releases**: New features, backward-compatible changes
- **Major releases**: Breaking changes, major new features

### Pre-release Checklist

Before creating a release:

- [ ] All tests pass
- [ ] Documentation is up to date
- [ ] CHANGELOG.md is updated
- [ ] Version numbers are updated
- [ ] Security review completed (for security-related changes)
- [ ] Performance impact assessed (for performance-related changes)

## Getting Help

### Communication Channels

- **GitHub Issues**: For bug reports and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: developers@metasoft.dev for security issues

### Documentation

- **README.md**: Package overview and quick start
- **Wiki**: Detailed documentation and examples
- **Code Comments**: Inline documentation for complex logic
- **Tests**: Living documentation of expected behavior

### Mentorship

New contributors are welcome! If you're new to:

- **Laravel**: Check out the [Laravel documentation](https://laravel.com/docs)
- **PHP**: Review [PHP best practices](https://phptherightway.com/)
- **Open Source**: Read the [Open Source Guide](https://opensource.guide/)

## Recognition

Contributors will be recognized in:

- README.md contributors section
- Release notes for significant contributions
- GitHub contributor graphs

## License

By contributing to Laravel Breeze 2FA, you agree that your contributions will be licensed under the same MIT License that covers the project.

---

Thank you for contributing to Laravel Breeze 2FA! Your efforts help make web authentication more secure and accessible for everyone. 🚀
