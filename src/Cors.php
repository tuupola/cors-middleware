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

namespace Tuupola\Middleware;

use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Strategies\Settings;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Cors
{
    private $options = [
        "origin" => "*",
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => [],
        "headers.expose" => [],
        "credentials" => false,
        "cache" => 0,
        "error" => null
    ];

    private $settings;

    protected $logger;

    public function __construct($options)
    {
        $this->settings = new Settings;

        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {

        $analyzer = Analyzer::instance($this->buildSettings($request, $response));
        if ($this->logger) {
            $analyzer->setLogger($this->logger);
        }
        $cors = $analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                return $this->error($request, $response, [
                    "message" => "CORS request origin is not allowed.",
                ])->withStatus(401);
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                return $this->error($request, $response, [
                    "message" => "CORS requested method is not supported.",
                ])->withStatus(401);
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $this->error($request, $response, [
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
                return $next($request, $response);
            default:
                /* Actual CORS request. */
                $response = $next($request, $response);
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
     * Hydate options from given array
     *
     * @param array $data Array of options.
     * @return self
     */
    private function hydrate(array $data = [])
    {
        foreach ($data as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = "set" . ucwords($key);
            $method = str_replace(" ", "", $method);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
        return $this;
    }

    private function buildSettings(RequestInterface $request, ResponseInterface $response)
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

    public function setOrigin($origin)
    {
        $this->options["origin"] = $origin;
        return $this;
    }

    public function setMethods($methods)
    {
        if (is_callable($methods)) {
            $this->options["methods"] = $methods->bindTo($this);
        } else {
            $this->options["methods"] = $methods;
        }

        return $this;
    }

    public function setHeadersAllow(array $headers)
    {
        $this->options["headers.allow"] = $headers;
        return $this;
    }

    public function setHeadersExpose(array $headers)
    {
        $this->options["headers.expose"] = $headers;
        return $this;
    }

    public function setCredentials($credentials)
    {
        $credentials = !!$credentials;
        $this->options["credentials"] = $credentials;
        return $this;
    }

    public function setCache($cache)
    {
        $this->options["cache"] = $cache;
        return $this;
    }

    /**
     * Set the logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get the logger
     *
     * @return Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the error handler
     *
     * @return string
     */
    public function getError()
    {
        return $this->options["error"];
    }

    /**
     * Set the error handler
     *
     * @return self
     */
    public function setError($error)
    {
        $this->options["error"] = $error;
        return $this;
    }

    /**
     * Call the error handler if it exists
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function error(RequestInterface $request, ResponseInterface $response, $arguments)
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
