<?php

/*

Copyright (c) 2016-2019 Mika Tuupola

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

/**
 * @see       https://github.com/tuupola/cors-middleware
 * @see       https://github.com/neomerx/cors-psr7
 * @see       https://www.w3.org/TR/cors/
 * @license   https://www.opensource.org/licenses/mit-license.php
 */

declare(strict_types=1);

namespace Tuupola\Middleware;

use Closure;
use Neomerx\Cors\Analyzer as CorsAnalyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface as CorsAnalysisResultInterface;
use Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;
use Neomerx\Cors\Strategies\Settings as CorsSettings;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Middleware\DoublePassTrait;

final class CorsMiddleware implements MiddlewareInterface
{
    use DoublePassTrait;

    private $logger;
    private $options = [
        "origin" => "*",
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => [],
        "headers.expose" => [],
        "credentials" => false,
        "origin.server" => null,
        "cache" => 0,
        "error" => null
    ];

    public function __construct($options = [])
    {
        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);
    }

    /**
     * Execute as PSR-15 middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = (new ResponseFactory())->createResponse();

        $analyzer = CorsAnalyzer::instance($this->buildSettings($request, $response));
        if ($this->logger) {
            $analyzer->setLogger($this->logger);
        }
        $cors = $analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case CorsAnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                $response = $response->withStatus(401);
                return $this->processError($request, $response, [
                    "message" => "CORS request origin is not allowed.",
                ]);
            case CorsAnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                $response = $response->withStatus(401);
                return $this->processError($request, $response, [
                    "message" => "CORS requested method is not supported.",
                ]);
            case CorsAnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                $response = $response->withStatus(401);
                return $this->processError($request, $response, [
                    "message" => "CORS requested header is not allowed.",
                ]);
            case CorsAnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $cors_headers = $cors->getResponseHeaders();
                foreach ($cors_headers as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (false === is_array($value)) {
                        $value = (string)$value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response->withStatus(200);
            case CorsAnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $handler->handle($request);
            default:
                /* Actual CORS request. */
                $response = $handler->handle($request);
                $cors_headers = $cors->getResponseHeaders();
                $cors_headers = $this->fixHeaders($cors_headers);

                foreach ($cors_headers as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (false === is_array($value)) {
                        $value = (string)$value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response;
        }
    }

    /**
     * Hydrate all options from the given array.
     */
    private function hydrate(array $data = []): void
    {
        foreach ($data as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = lcfirst(ucwords($key));
            $method = str_replace(" ", "", $method);
            $callable = [$this, $method];

            if (is_callable($callable)) {
                /* Try to use setter */
                call_user_func($callable, $value);
            } else {
                /* Or fallback to setting option directly */
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Build a CORS settings object.
     */
    private function buildSettings(ServerRequestInterface $request, ResponseInterface $response): CorsSettings
    {
        $settings = new CorsSettings();

        $origin = array_fill_keys((array) $this->options["origin"], true);
        $settings->setRequestAllowedOrigins($origin);

        if (is_callable($this->options["methods"])) {
            $methods = (array) $this->options["methods"]($request, $response);
        } else {
            $methods = $this->options["methods"];
        }
        $methods = array_fill_keys($methods, true);
        $settings->setRequestAllowedMethods($methods);

        $headers = array_fill_keys($this->options["headers.allow"], true);
        $headers = array_change_key_case($headers, CASE_LOWER);
        $settings->setRequestAllowedHeaders($headers);

        $headers = array_fill_keys($this->options["headers.expose"], true);
        $settings->setResponseExposedHeaders($headers);

        $settings->setRequestCredentialsSupported($this->options["credentials"]);

        if (is_string($this->options["origin.server"])) {
            $settings->setServerOrigin($this->options["origin.server"]);
        }

        $settings->setPreFlightCacheMaxAge($this->options["cache"]);

        return $settings;
    }

    /**
     * Edge cannot handle multiple Access-Control-Expose-Headers headers
     */
    private function fixHeaders(array $headers): array
    {
        if (isset($headers[CorsResponseHeaders::EXPOSE_HEADERS])) {
            $headers[CorsResponseHeaders::EXPOSE_HEADERS] =
                implode(",", $headers[CorsResponseHeaders::EXPOSE_HEADERS]);
        }
        return $headers;
    }

    /**
     * Set allowed origin.
     */
    private function origin($origin): void
    {
        $this->options["origin"] = (array) $origin;
    }

    /**
     * Set request methods to be allowed.
     */
    private function methods($methods): void
    {
        if (is_callable($methods)) {
            if ($methods instanceof Closure) {
                $this->options["methods"] = $methods->bindTo($this);
            } else {
                $this->options["methods"] = $methods;
            }
        } else {
            $this->options["methods"] = (array) $methods;
        }
    }

    /**
     * Set headers to be allowed.
     */
    private function headersAllow(array $headers): void
    {
        $this->options["headers.allow"] = $headers;
    }

    /**
     * Set headers to be exposed.
     */
    private function headersExpose(array $headers): void
    {
        $this->options["headers.expose"] = $headers;
    }

    /**
     * Enable or disable cookies and authentication.
     */
    private function credentials(bool $credentials): void
    {
        $this->options["credentials"] = $credentials;
    }

    /**
     * Set the server origin.
     */
    private function originServer(?string $origin): void
    {
        $this->options["origin.server"] = $origin;
    }

    /**
     * Set the cache time in seconds.
     */
    private function cache(int $cache): void
    {
        $this->options["cache"] = $cache;
    }

    /**
     * Set the error handler.
     */
    private function error(callable $error): void
    {
        if ($error instanceof Closure) {
            $this->options["error"] = $error->bindTo($this);
        } else {
            $this->options["error"] = $error;
        }
    }

    /**
     * Set the PSR-3 logger.
     */
    private function logger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Call the error handler if it exists.
     */
    private function processError(ServerRequestInterface $request, ResponseInterface $response, array $arguments = null)
    {
        if (is_callable($this->options["error"])) {
            $handler_response = $this->options["error"]($request, $response, $arguments);
            if (is_a($handler_response, "\Psr\Http\Message\ResponseInterface")) {
                return $handler_response;
            }
        }
        return $response;
    }
}
