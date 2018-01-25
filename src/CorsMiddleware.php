<?php

/*
 * This file is part of the CORS middleware package
 *
 * Copyright (c) 2016-2018 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * See also:
 *   https://github.com/tuupola/cors-middleware
 *   https://github.com/neomerx/cors-psr7
 *   https://www.w3.org/TR/cors/
 */


declare(strict_types=1);

namespace Tuupola\Middleware;

use Neomerx\Cors\Analyzer as CorsAnalyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface as CorsAnalysisResultInterface;
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
        $response = (new ResponseFactory)->createResponse();

        $analyzer = CorsAnalyzer::instance($this->buildSettings($request, $response));
        if ($this->logger) {
            $analyzer->setLogger($this->logger);
        }
        $cors = $analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case CorsAnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                return $this->processError($request, $response, [
                    "message" => "CORS request origin is not allowed.",
                ])->withStatus(401);
            case CorsAnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                return $this->processError($request, $response, [
                    "message" => "CORS requested method is not supported.",
                ])->withStatus(401);
            case CorsAnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $this->processError($request, $response, [
                    "message" => "CORS requested header is not allowed.",
                ])->withStatus(401);
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
            if (method_exists($this, $method)) {
                /* Try to use setter */
                call_user_func([$this, $method], $value);
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
        $settings = new CorsSettings;

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

        $settings->setPreFlightCacheMaxAge($this->options["cache"]);

        return $settings;
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
            $this->options["methods"] = $methods->bindTo($this);
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
        $this->options["error"] = $error->bindTo($this);
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
