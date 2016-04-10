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
        "origin" => ["*"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => [],
        "headers.expose" => [],
        "credentials" => false,
        "cache" => 0
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

        $analyzer = Analyzer::instance($this->settings);
        if ($this->logger) {
            $analyzer->setLogger($this->logger);
        }
        $cors = $analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $response->withStatus(401);
            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $cors_headers = $cors->getResponseHeaders();
                foreach ($cors_headers as $header => $value) {
                    $response = $response->withHeader($header, $value);
                }
                return $response->withStatus(200);
            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $next($request, $response);
            default:
                /* actual CORS request */
                $cors_headers = $cors->getResponseHeaders();
                foreach ($cors_headers as $header => $value) {
                    $response = $response->withHeader($header, $value);
                }
                return $next($request, $response);
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
            $method = "set" . ucwords($key, ".");
            $method = str_replace(".", "", $method);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
        return $this;
    }

    public function setOrigin(array $origin)
    {
        $this->options["origin"] = $origin;
        $origin = array_fill_keys($origin, true);
        $this->settings->setRequestAllowedOrigins($origin);
        return $this;
    }

    public function setMethods(array $methods)
    {
        $this->options["methods"] = $methods;
        $methods = array_fill_keys($methods, true);
        $this->settings->setRequestAllowedMethods($methods);
        return $this;
    }

    public function setHeadersAllow(array $headers)
    {
        $this->options["headers.allow"] = $headers;
        $headers = array_fill_keys($headers, true);
        $headers = array_change_key_case($headers, CASE_LOWER);
        $this->settings->setRequestAllowedHeaders($headers);
        return $this;
    }

    public function setHeadersExpose(array $headers)
    {
        $this->options["headers.expose"] = $headers;
        $headers = array_fill_keys($headers, true);
        $this->settings->setResponseExposedHeaders($headers);
        return $this;
    }

    public function setCredentials($credentials)
    {
        $$credentials = !!$credentials;
        $this->options["credentials"] = $credentials;
        $this->settings->setRequestCredentialsSupported($credentials);
        return $this;
    }

    public function setCache($cache)
    {
        $this->options["cache"] = $cache;
        $this->settings->setPreFlightCacheMaxAge($cache);
        return $this;
    }

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger()
    {
        return $this->logger = $logger;
    }

}
