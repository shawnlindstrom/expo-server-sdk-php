# Changelog

All notable changes to `expo-server-sdk-php` will be documented in this file.

## [1.0.1] - 2026-01-31

### Continuous Integration

- Added friendsofphp/php-cs-fixer to dev dependencies
- Added composer script for running code style check and fix

## [1.0.0] - 2026-01-31

### Fork / Maintenance

This repository is a modernization fork of [`ctwillie/expo-server-sdk-php`](https://github.com/ctwillie/expo-server-sdk-php), starting from upstream commit `9a433a7`.

### Changed

- **BREAKING:** Minimum PHP version raised to 8.4
- Upgraded PHPUnit from 9.x to 12.5.8
- Replaced deprecated `@test` annotations with `#[Test]` attributes
- Added `declare(strict_types=1)` to all classes
- Modernized type declarations across the codebase (property types, parameter types, return types)
- Fixed PHP 8.4 deprecations (implicit nullable types, `${var}` string interpolation)

### Added

- Comprehensive docblocks with detailed parameter and return type annotations
- Additional exception annotations for better IDE support
- Extensive test coverage for edge cases across all components
- Security warning for `Macroable::macro()` method
- JSON error handling improvements using `JSON_THROW_ON_ERROR`
- Compression constants in `ExpoClient`

### Improved

- Token validation logic and error messages
- Exception handling consistency across drivers and core classes
- Type safety throughout the codebase
- Code organization and readability
- Documentation and inline comments

### Fixed

- File path validation in `FileDriver`
- JSON validation in `File` class
- Array filtering logic in `ExpoMessage::toArray()`
- Exception handling in `DriverManager`

[1.0.0]: https://github.com/shawnlindstrom/expo-server-sdk-php/releases/tag/1.0.0
