<?php

/*
 * This file is part of the CORS middleware package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/cors-middleware
 *
 */

declare(strict_types=1);

namespace Tuupola\Middleware;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Middleware\Cors\CallableHandler;

class Cors implements MiddlewareInterface
{
    protected $logger;
    private $settings;
    private $options = [
        "origin" => "*",
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => [],
        "headers.expose" => [],
        "credentials" => false,
        "cache" => 0,
        "error" => null
    ];

    public function __construct($options)
    {
        $this->settings = new Settings;

        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $this->process($request, new CallableHandler($next, $response));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = (new ResponseFactory)->createResponse();

        $analyzer = Analyzer::instance($this->buildSettings($request, $response));
        if ($this->logger) {
            $analyzer->setLogger($this->logger);
        }
        $cors = $analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                return $this->processError($request, $response, [
                    "message" => "CORS request origin is not allowed.",
                ])->withStatus(401);
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                return $this->processError($request, $response, [
                    "message" => "CORS requested method is not supported.",
                ])->withStatus(401);
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $this->processError($request, $response, [
                    "message" => "CORS requested header is not allowed.",
                ])->withStatus(401);
            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $cors_headers = $cors->getResponseHeaders();
                foreach ($cors_headers as $header => $value) {
                    /* Diactoros errors on integer values. */
                    if (false === is_array($value)) {
                        $value = (string)$value;
                    }
                    $response = $response->withHeader($header, $value);
                }
                return $response->withStatus(200);
            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
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
     * Hydrate all options from the given array
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
                print $key;
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Builds the neomerc/cors settings object
     */
    private function buildSettings(ServerRequestInterface $request, ResponseInterface $response)
    {
        $origin = array_fill_keys((array) $this->options["origin"], true);
        $this->settings->setRequestAllowedOrigins($origin);

        if (is_callable($this->options["methods"])) {
            $methods = (array) $this->options["methods"]($request, $response);
        } else {
            $methods = $this->options["methods"];
        }
        $methods = array_fill_keys($methods, true);
        $this->settings->setRequestAllowedMethods($methods);

        $headers = array_fill_keys($this->options["headers.allow"], true);
        $headers = array_change_key_case($headers, CASE_LOWER);
        $this->settings->setRequestAllowedHeaders($headers);

        $headers = array_fill_keys($this->options["headers.expose"], true);
        $this->settings->setResponseExposedHeaders($headers);

        $this->settings->setRequestCredentialsSupported($this->options["credentials"]);

        $this->settings->setPreFlightCacheMaxAge($this->options["cache"]);

        return $this->settings;
    }

    private function origin($origin): void
    {
        $this->options["origin"] = (array) $origin;
    }

    private function methods($methods): void
    {
        if (is_callable($methods)) {
            $this->options["methods"] = $methods->bindTo($this);
        } else {
            $this->options["methods"] = (array) $methods;
        }
    }

    private function headersAllow(array $headers): void
    {
        $this->options["headers.allow"] = $headers;
    }

    private function headersExpose(array $headers): void
    {
        $this->options["headers.expose"] = $headers;
    }

    private function credentials(bool $credentials): void
    {
        $this->options["credentials"] = $credentials;
    }

    private function cache(int $cache): void
    {
        $this->options["cache"] = $cache;
    }

    private function error(callable $error): void
    {
        $this->options["error"] = $error->bindTo($this);
    }

    private function logger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Call the error handler if it exists
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
