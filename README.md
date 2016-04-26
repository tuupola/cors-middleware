# PSR-7 CORS Middleware

This middleware implements [Cross-origin resource sharing](https://en.wikipedia.org/wiki/Cross-origin_resource_sharing). It was originally developed for Slim but can be used with all frameworks using PSR-7 style middlewares. It has been tested  with [Slim Framework](http://www.slimframework.com/) and [Zend Expressive](https://zendframework.github.io/zend-expressive/). Internally the middleware uses [neomerx/cors-psr7](https://github.com/neomerx/cors-psr7) library for heavy lifting.

[![Latest Version](https://img.shields.io/packagist/v/tuupola/cors-middleware.svg?style=flat-square)](https://packagist.org/packages/tuupola/cors-middleware)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/tuupola/cors-middleware/master.svg?style=flat-square)](https://travis-ci.org/tuupola/cors-middleware)
[![HHVM Status](https://img.shields.io/hhvm/tuupola/cors-middleware.svg?style=flat-square)](http://hhvm.h4cc.de/package/tuupola/cors-middleware)
[![Coverage](http://img.shields.io/codecov/c/github/tuupola/cors-middleware.svg?style=flat-square)](https://codecov.io/github/tuupola/cors-middleware)

## Install

Install using [composer](https://getcomposer.org/).

``` bash
$ composer require tuupola/cors-middleware
```

## Usage

Documentation assumes you have working knowledge of CORS. There are no mandatory parameters. If called without any parameters the following defaults are used. Examples assume you are using Slim Framework.

```php
$app = new \Slim\App();

$app->add(new \Tuupola\Middleware\Cors([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
    "headers.allow" => [],
    "headers.expose" => [],
    "credentials" => false,
    "cache" => 0,
]));
```

```bash
$ curl "https://api.example.com/" \
    --request OPTIONS \
    --include
    --header "Access-Control-Request-Method: PUT" \
    --header "Origin: http://www.example.com"

HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://www.example.com
Vary: Origin
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE
```

However, you most likely want to change some of the defaults. For example if developing a REST API which supports caching and conditional requests you could use the following.


```php
$app = new \Slim\App();

$app->add(new \Tuupola\Middleware\Cors([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
    "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
    "headers.expose" => ["Authorization", "Etag"],
    "credentials" => true,
    "cache" => 86400
]));
```

```bash
$ curl "https://api.example.com/" \
    --request OPTIONS \
    --include \
    --header "Access-Control-Request-Method: PUT" \
    --header "Origin: http://www.example.com" \
    --header "Access-Control-Request-Headers: Authorization"

HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://www.example.com
Access-Control-Allow-Credentials: true
Vary: Origin
Access-Control-Max-Age: 60
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE
Access-Control-Allow-Headers: authorization, if-match, if-unmodified-since
```

## Other parameters

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

Error is called when CORS request fails. It receives last error message in arguments. This can be used for example to create `application/json` responses when CORS request fails.

``` php
$app = new \Slim\App();

$app->add(new \Tuupola\Middleware\Cors([
    "error" => function ($request, $response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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
