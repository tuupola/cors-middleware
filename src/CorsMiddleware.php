<?php

/*

Copyright (c) 2016-2022 Mika Tuupola

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Middleware\Settings as CorsSettings;

final class CorsMiddleware implements MiddlewareInterface
{
    use DoublePassTrait;

    /** @var int */
    private const PORT_HTTP = 80;

    /** @var int */
    private const PORT_HTTPS = 443;

    /** @var LoggerInterface|null */
    private $logger;

    /**
     * @var array{
     *  origin: array<string>,
     *  methods: array<string>|callable|null,
     *  "headers.allow": array<string>,
     *  "headers.expose": array<string>,
     *  credentials: bool,
     *  "origin.server": null|string,
     *  cache: int,
     *  error: null|callable,
     *  logger: null|LoggerInterface,
     * }
     */
    private $options = [
        "origin" => ["*"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => [],
        "headers.expose" => [],
        "credentials" => false,
        "origin.server" => null,
        "cache" => 0,
        "error" => null,
        "logger" => null,
    ];

    /**
     * @param array{
     *  origin?: string|array<string>,
     *  methods?: array<string>|callable|null,
     *  "headers.allow"?: array<string>,
     *  "headers.expose"?: array<string>,
     *  credentials?: bool,
     *  "origin.server"?: null|string,
     *  cache?: int,
     *  error?: null|callable,
     *  logger?: null|LoggerInterface,
     * } $options
     */
    public function __construct(array $options = [])
    {
        /* TODO: This only exists to for BC. */
        if (isset($options["origin"])) {
            $options["origin"] = (array) $options["origin"];
        }

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
        if ($this->logger !== null) {
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
                    if (! is_array($value)) {
                        $value = (string) $value;
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
                    if (! is_array($value)) {
                        $value = (string) $value;
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
                /**
                 * Or fallback to setting option directly
                 * Shouldn't be in use as every option is covered by setters
                 *
                 * @phpstan-ignore-next-line
                 */
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

        $serverOrigin = $this->determineServerOrigin();

        $settings->init(
            $serverOrigin["scheme"],
            $serverOrigin["host"],
            $serverOrigin["port"]
        );

        $settings->setAllowedOrigins($this->options["origin"]);

        if (is_callable($this->options["methods"])) {
            $methods = (array) $this->options["methods"]($request, $response);
        } else {
            $methods = (array) $this->options["methods"];
        }

        $settings->setAllowedMethods($methods);

        /* transform all headers to lowercase */
        $headers = array_change_key_case($this->options["headers.allow"]);

        $settings->setAllowedHeaders($headers);

        $settings->setExposedHeaders($this->options["headers.expose"]);

        if ($this->options["credentials"]) {
            $settings->setCredentialsSupported();
        }

        $settings->setPreFlightCacheMaxAge($this->options["cache"]);

        return $settings;
    }

    /**
     * Try to determine the server origin uri fragments
     *
     * @return array{scheme: string, host: string, port: int}
     */
    private function determineServerOrigin(): array
    {
        /* Set defaults */
        $url = [
            "scheme" => "https",
            "host" => "localhost",
            "port" => self::PORT_HTTPS,
        ];

        /* Load details from server origin */
        if (is_string($this->options["origin.server"])) {
            /** @var false|array{scheme: string, host: string, port?: int} $url_chunks */
            $url_chunks = parse_url($this->options["origin.server"]);
            if ($url_chunks !== false) {
                $url = $url_chunks;
            }

            if (! array_key_exists("port", $url)) {
                $url["port"] = $url["scheme"] === "https" ? self::PORT_HTTPS : self::PORT_HTTP;
            }
        }

        return $url;
    }

    /**
     * Edge cannot handle Access-Control-Expose-Headers having a trailing whitespace after the comma
     *
     * @see https://github.com/tuupola/cors-middleware/issues/40
     */
    private function fixHeaders(array $headers): array
    {
        if (isset($headers[CorsResponseHeaders::EXPOSE_HEADERS])) {
            $headers[CorsResponseHeaders::EXPOSE_HEADERS] =
                str_replace(
                    " ",
                    "",
                    $headers[CorsResponseHeaders::EXPOSE_HEADERS]
                );
        }

        return $headers;
    }

    /**
     * Set allowed origin.
     */
    private function origin(array $origin): void
    {
        $this->options["origin"] = $origin;
    }

    /**
     * Set request methods to be allowed.
     * @param callable|array $methods.
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
    private function logger(LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }

    /**
     * Call the error handler if it exists.
     */
    private function processError(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments = null
    ): ResponseInterface {
        if (is_callable($this->options["error"])) {
            $handler_response = $this->options["error"]($request, $response, $arguments);
            if (is_a($handler_response, ResponseInterface::class)) {
                return $handler_response;
            }
        }

        return $response;
    }
}
