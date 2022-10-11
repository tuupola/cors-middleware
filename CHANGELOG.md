# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [1.4.3](https://github.com/tuupola/cors-middleware/compare/1.4.2...1.4.3) - 2020-10-11
### Fixed
- TSanitizedOptions causing PHPStan error ([#78](https://github.com/tuupola/cors-middleware/issues/78), [#79](https://github.com/tuupola/cors-middleware/pull/79)).

## [1.4.2](https://github.com/tuupola/cors-middleware/compare/1.4.1...1.4.2) - 2022-10-10
### Fixed
- AssertionError if user had `zend.assertions` enabled php.ini ([#75](https://github.com/tuupola/cors-middleware/pull/75), [#76](https://github.com/tuupola/cors-middleware/pull/76)).

## [1.4.1](https://github.com/tuupola/cors-middleware/compare/1.4.0...1.4.1) - 2022-10-07
### Fixed
- PHPStan annotations for the constructor ([#73](https://github.com/tuupola/cors-middleware/pull/73)).

## [1.4.0](https://github.com/tuupola/cors-middleware/compare/1.3.0...1.4.0) - 2022-10-06
### Added
- Support for `neomerx/cors-psr7:^3.0` ([#72](https://github.com/tuupola/cors-middleware/pull/72)).

### Changed
- PHP 7.2 is now the minimum requirement ([#63](https://github.com/tuupola/cors-middleware/pull/63)).
- PHPStan now uses strict rules ([#63](https://github.com/tuupola/cors-middleware/pull/63)).
- Upgrade to `neomerx/cors-psr7:^2.0` ([#67](https://github.com/tuupola/cors-middleware/pull/67)).

## [1.3.0](https://github.com/tuupola/cors-middleware/compare/1.2.1...1.3.0) - 2022-04-13
### Added
- Support for wildcard origins ([#56](https://github.com/tuupola/cors-middleware/pull/56)).

## [1.2.1](https://github.com/tuupola/cors-middleware/compare/1.2.0...1.2.1) - 2020-10-29
### Fixed
- Bump minimum requirement of `tuupola/http-factory` to `1.0.2` . This is to avoid Composer 2 installing the broken `1.0.1` version which will also cause `psr/http-factory` to be removed. ([#50](https://github.com/tuupola/cors-middleware/pull/50))

## [1.2.0](https://github.com/tuupola/cors-middleware/compare/1.1.1...1.2.0) - 2020-09-09
### Added
- Allow installing with PHP 8 ([#49](https://github.com/tuupola/cors-middleware/pull/49)).

## [1.1.1](https://github.com/tuupola/cors-middleware/compare/1.1.0...1.1.1) - 2019-10-30
### Changed
- Concatenate `Access-Control-Expose-Headers` values with comma instead of comma and space ([#44](https://github.com/tuupola/cors-middleware/pull/44)). Edge has issues with spaces.

## [1.1.0](https://github.com/tuupola/cors-middleware/compare/1.0.0...1.1.0) - 2019-10-08
### Changed
- Send multiple `Access-Control-Expose-Headers` values in one header ([#40](https://github.com/tuupola/cors-middleware/issues/40), [#42](https://github.com/tuupola/cors-middleware/pull/42)).
- Coding standard is now PSR-12 ([#35](https://github.com/tuupola/cors-middleware/pull/35))

## [1.0.0](https://github.com/tuupola/cors-middleware/compare/0.9.4...1.0.0) - 2019-06-04
### Changed
- `tuupola/callable-handler:^1.0` is now minimum requirement.
- `tuupola/http-factory:^1.0` is now minimum requirement.

## [0.9.4](https://github.com/tuupola/cors-middleware/compare/0.9.3...0.9.4) - 2019-03-24
### Changed
- `psr/http-message:^1.0.1` is now minimum requirement.

### Added
- Static analysis ([#32](https://github.com/tuupola/cors-middleware/pull/32)).

## [0.9.3](https://github.com/tuupola/cors-middleware/compare/0.9.2...0.9.3) - 2019-02-25
### Fixed
- Allow `error` handler to override HTTP status code ([#30](https://github.com/tuupola/cors-middleware/issues/30), [#31](https://github.com/tuupola/cors-middleware/pull/31)).

## [0.9.2](https://github.com/tuupola/cors-middleware/compare/0.9.1...0.9.2) - 2019-01-26
### Fixed
- Do not assume `error` and `methods` callables are an instance of a `Closure` ([#26](https://github.com/tuupola/cors-middleware/issues/26)).

## [0.9.1](https://github.com/tuupola/cors-middleware/compare/0.9.0...0.9.1) - 2018-10-15
### Added
- Support for `tuupola/callable-handler:^1.0` and `tuupola/http-factory:^1.0`

### Changed
- `neomerx/cors-psr7:^1.0.4` is now minimum requirement.

## [0.9.0](https://github.com/tuupola/cors-middleware/compare/0.8.0...0.9.0) - 2018-08-21
### Added
- New option `origin.server` to specify the origin of the server. Helps when same-origin requests include a valid but unesseccary `Origin` header ([#22](https://github.com/tuupola/cors-middleware/pull/22), [#23](https://github.com/tuupola/cors-middleware/pull/23)).

## [0.8.0](https://github.com/tuupola/cors-middleware/compare/0.7.0...0.8.0) - 2018-08-07
### Added
- Support for the stable version of PSR-17

### Changed
- Use released version of [equip/dispatch](https://github.com/equip/dispatch) in tests.

## [0.7.0](https://github.com/tuupola/cors-middleware/compare/0.6.0...0.7.0) - 2017-01-25
### Added
- Support for the [approved version of PSR-15](https://github.com/php-fig/http-server-middleware).

## [0.6.0](https://github.com/tuupola/cors-middleware/compare/0.5.2...0.6.0) - 2017-12-25
### Added
- Support for the [latest version of PSR-15](https://github.com/http-interop/http-server-middleware).
- Methods setting can now be either an array or callable returning an array. This is useful if your framework makes it possible to retrieve defined methods for a given route.

    ```php
    $app->add(new \Tuupola\Middleware\CorsMiddleware([
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
    ]));
    ```
    ```php
    $app->add(new \Tuupola\Middleware\CorsMiddleware([
        "methods" => function(ServerRequestInterface $request) {
            /* Some logic to figure out allowed $methods. */
            return $methods;
        }
    ]));
    ```

### Changed
- Classname changed from Cors to CorsMiddleware.
- Settings can now be passed only in the constructor.
- PHP 7.1 is now minimum requirement.
- Inside error handler `$this` now refers to the middleware itself.
- PSR-7 double pass is now supported using [tuupola/callable-handler](https://github.com/tuupola/callable-handler) library.

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
