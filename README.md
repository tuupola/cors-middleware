# PSR-7 and PSR-15 CORS Middleware

This middleware implements [Cross-origin resource sharing](https://en.wikipedia.org/wiki/Cross-origin_resource_sharing). It supports both PSR-7 style doublepass and PSR-15 middleware standards. It has been tested  with [Slim Framework](http://www.slimframework.com/) and [Zend Expressive](https://zendframework.github.io/zend-expressive/). Internally the middleware uses [neomerx/cors-psr7](https://github.com/neomerx/cors-psr7) library for heavy lifting.

[![Latest Version](https://img.shields.io/packagist/v/tuupola/cors-middleware.svg?style=flat-square)](https://packagist.org/packages/tuupola/cors-middleware)
[![Packagist](https://img.shields.io/packagist/dm/tuupola/cors-middleware.svg)](https://packagist.org/packages/tuupola/cors-middleware)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/tuupola/cors-middleware/Tests/master?style=flat-square)](https://github.com/tuupola/cors-middleware/actions)
[![Coverage](https://img.shields.io/codecov/c/github/tuupola/cors-middleware.svg?style=flat-square)](https://codecov.io/github/tuupola/cors-middleware)

## Install

Install using [composer](https://getcomposer.org/).

``` bash
$ composer require tuupola/cors-middleware
```

## Usage

Documentation assumes you have working knowledge of CORS. There are no mandatory parameters. If you are using Zend Expressive skeleton middlewares are added to file called `config/pipeline.php`. Note that you must disable the default `ImplicitOptionsMiddleware` for this middleware to work.

```php
use Tuupola\Middleware\CorsMiddleware;

#$app->pipe(ImplicitOptionsMiddleware::class);
$app->pipe(CorsMiddleware::class);
```

Slim Framework does not have specified config files. Otherwise adding the middleware is similar with previous.

```php
$app->add(new Tuupola\Middleware\CorsMiddleware);
```

Rest of the examples use Slim Framework.

If called without any parameters the following defaults are used.

```php
$app->add(new Tuupola\Middleware\CorsMiddleware([
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
$app->add(new Tuupola\Middleware\CorsMiddleware([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
    "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
    "headers.expose" => ["Etag"],
    "credentials" => true,
    "cache" => 86400
]));
```

```bash
$ curl "https://api.example.com/foo" \
    --request OPTIONS \
    --include \
    --header "Origin: http://www.example.com" \
    --header "Access-Control-Request-Method: PUT" \
    --header "Access-Control-Request-Headers: Authorization, If-Match"

HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://www.example.com
Access-Control-Allow-Credentials: true
Vary: Origin
Access-Control-Max-Age: 86400
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE
Access-Control-Allow-Headers: authorization, if-match, if-unmodified-since
```

```bash
$ curl "https://api.example.com/foo" \
    --request PUT \
    --include \
    --header "Origin: http://www.example.com"

HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://www.example.com
Access-Control-Allow-Credentials: true
Vary: Origin
Access-Control-Expose-Headers: Etag
```

## Parameters

### Origin

By default all origins are allowed. You can limit allowed origins by passing them as an array.

```php
$app->add(new Tuupola\Middleware\CorsMiddleware([
    "origin" => ["app-1.example.com", "app-2.example.com"]
]));
```

You can also use wildcards to define multiple origins at once. Wildcards are matched by using the [fnmatch()](https://www.php.net/manual/en/function.fnmatch.php) function.

```php
$app->add(new Tuupola\Middleware\CorsMiddleware([
    "origin" => ["*.example.com"]
]));
```

### Methods

Methods can be passed either as an array or a callable which returns an array. Below example is for Zend Expressive where value of `methods` is dynamic depending on the requested route.

``` php
use Tuupola\Middleware\CorsMiddleware;
use Zend\Expressive\Router\RouteResult;

$app->pipe(new CorsMiddleware([
    "origin" => ["*"],
    "methods" => function($request) {
        $result = $request->getAttribute(RouteResult::class);
        $route = $result->getMatchedRoute();
        return $route->getAllowedMethods();
    }
]));
```

Same thing for Slim 3. This assumes you have **not** defined the `OPTIONS` route.

``` php
use Fastroute\Dispatcher;
use Tuupola\Middleware\CorsMiddleware;

$app->add(
    new CorsMiddleware([
        "origin" => ["*"],
        "methods" => function($request) use ($app) {
            $container = $app->getContainer();
            $dispatch = $container["router"]->dispatch($request);
            if (Dispatcher::METHOD_NOT_ALLOWED === $dispatch[0]) {
                return $dispatch[1];
            }
        }
    ])
);
```

### Logger

The optional `logger` parameter allows you to pass in a PSR-3 compatible logger to help with debugging or other application logging needs.

``` php
$logger = Monolog\Logger("slim");
$rotating = new RotatingFileHandler(__DIR__ . "/logs/slim.log", 0, Logger::DEBUG);
$logger->pushHandler($rotating);

$app->add(new Tuupola\Middleware\CorsMiddleware([
    "logger" => $logger,
]));
```

### Error

Error is called when CORS request fails. It receives last error message in arguments. This can be used for example to create `application/json` responses when CORS request fails.

``` php
$app->add(new Tuupola\Middleware\CorsMiddleware([
    "methods" => ["GET", "POST", "PUT"],
    "error" => function ($request, $response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));
```

```
$ curl https://api.example.com/foo \
    --request OPTIONS \
    --include \
    --header "Access-Control-Request-Method: PATCH" \
    --header "Origin: http://www.example.com"

HTTP/1.1 401 Unauthorized
Content-Type: application/json
Content-Length: 83

{
    "status": "error",
    "message": "CORS requested method is not supported."
}
```

### Server origin

If your same-origin requests contain an unnecessary `Origin` header, they might get blocked in case the server origin is not among the allowed origins already. In this case you can use the optional `origin.server` parameter to specify the origin of the server.

``` php
$app->add(new Tuupola\Middleware\CorsMiddleware([
    "origin.server" => "https://example.com"
]));
```

```
$ curl https://example.com/api \
    --request POST \
    --include \
    --header "Origin: https://example.com"

HTTP/1.1 200 OK
```

## Testing

You can run tests either manually or automatically on every code change. Automatic tests require [entr](http://entrproject.org/) to work.

``` bash
$ make test
```

``` bash
$ brew install entr
$ make watch
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email tuupola@appelsiini.net instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
