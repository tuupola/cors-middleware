# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [0.6.0](https://github.com/tuupola/cors-middleware/compare/0.5.2...0.6.0) - unreleased
### Added
- Support for the [latest version of PSR-15](https://github.com/http-interop/http-server-middleware).

### Changed
- Classname changed from Cors to CorsMiddleware.
- Settings can now be passed only in the constructor.
- Origin must now always be passed as an array.
- PHP 7.1 is now minimum requirement.
- Inside error handler `$this` now refers to the middleware itself.

### Removed
- Support for PHP 5.X. PSR-15 is now PHP 7.x only.
- Public getters and setters for the settings.

## [0.5.2](https://github.com/tuupola/cors-middleware/compare/0.5.1...0.5.2) - 2016-08-12
### Fixed
- Middleware was overriding the passed in response ([#1](https://github.com/tuupola/cors-middleware/issues/1)).

## [0.5.1](https://github.com/tuupola/cors-middleware/compare/0.5.0...0.5.1) - 2016-04-25
### Fixed
- Diactoros was erroring with integer header values.

## 0.5.0 - 2016-04-25
Initial release. Support PSR-7 style doublepass middlewares.
