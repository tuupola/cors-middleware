# [WIP] PSR-7 CORS Middleware

[![Latest Version](https://img.shields.io/github/release/tuupola/cors-middleware.svg?style=flat-square)](https://github.com/tuupola/cors-middleware/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/tuupola/cors-middleware/master.svg?style=flat-square)](https://travis-ci.org/tuupola/cors-middleware)
[![HHVM Status](https://img.shields.io/hhvm/tuupola/cors-middleware.svg?style=flat-square)](http://hhvm.h4cc.de/package/tuupola/cors-middleware)
[![Coverage](http://img.shields.io/codecov/c/github/tuupola/cors-middleware.svg?style=flat-square)](https://codecov.io/github/tuupola/cors-middleware)

## Install

Install Slim 3 version using [composer](https://getcomposer.org/).

``` bash
$ composer require tuupola/cors-middleware:dev-master
```

## Usage

## Optional parameters

### Logger

The optional `logger` parameter allows you to pass in a PSR-3 compatible logger to help with debugging or other application logging needs.

``` php
$app = new \Slim\App();

$logger = \Monolog\Logger("slim");
$rotating = new RotatingFileHandler(__DIR__ . "/logs/slim.log", 0, Logger::DEBUG);
$logger->pushHandler($rotating);

$app->add(new \Tuupola\Middleware\Cors([
    "logger" => $logger,
]));
```

### Error

Error is called when authentication fails. It receives last error message in arguments.

```php
$app = new \Slim\App();

$app->add(new \Tuupola\Middleware\Cors([
    "error" => function ($request, $response, $arguments) use ($app) {
        return $response->write("Error");
    }
]));
```

## Testing

You can run tests either manually...

``` bash
$ vendor/bin/phpunit
$ vendor/bin/phpcs --standard=PSR2 src/ -p
```

... or automatically on every code change.

``` bash
$ npm install
$ grunt watch
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email tuupola@appelsiini.net instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
