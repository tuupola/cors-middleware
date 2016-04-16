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

namespace Tuupola\Cors\Test;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Body;
use Slim\Http\Collection;

use Tuupola\Middleware\Cors;

class CorsTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    public function testShouldReturn200ByDefault()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers();
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("GET", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
        //$this->assertEquals("", $response->getBody());
    }

    public function testShouldHaveCorsHeaders()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Origin" => "http://www.example.com"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("GET", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals("http://www.example.com", $response->getHeaderLine("Access-Control-Allow-Origin"));
        $this->assertEquals("true", $response->getHeaderLine("Access-Control-Allow-Credentials"));
        $this->assertEquals("Origin", $response->getHeaderLine("Vary"));
        $this->assertEquals("Authorization,Etag", $response->getHeaderLine("Access-Control-Expose-Headers"));
    }

    public function testShouldReturn401WithWrongOrigin()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Origin" => "http://www.foo.com"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("GET", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["http://www.example.com"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn401WithWrongMethod()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Origin" => "http://www.example.com",
            "Access-Control-Request-Headers" => "Authorization",
            "Access-Control-Request-Method" => "PUT"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("OPTIONS", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn401WithWrongHeader()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Origin" => "http://www.example.com",
            "Access-Control-Request-Headers" => "X-Nosuch",
            "Access-Control-Request-Method" => "PUT"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("OPTIONS", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn200WithProperPreflightRequest()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Origin" => "http://www.example.com",
            "Access-Control-Request-Headers" => "Authorization",
            "Access-Control-Request-Method" => "PUT"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("OPTIONS", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /* https://github.com/neomerx/cors-psr7/issues/19
    public function testShouldReturn401WithWrongHost()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Host" => "www.nosuch.com",
            "Origin" => "http://www.example.com/",
            "Access-Control-Request-Headers" => "Authorization",
            "Access-Control-Request-Method" => "PUT"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("OPTIONS", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }
    */

    public function testShouldCallError()
    {
        $uri = Uri::createFromString("https://example.com/api");
        $headers = new Headers([
            "Origin" => "http://www.example.com",
            "Access-Control-Request-Headers" => "X-Nosuch",
            "Access-Control-Request-Method" => "PUT"
        ]);
        $cookies = [];
        $server = [];
        $body = new Body(fopen("php://temp", "r+"));
        $request = new Request("OPTIONS", $uri, $headers, $cookies, $server, $body);
        $response = new Response();
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400,
            "error" => function ($request, $response, $arguments) {
                return $response
                    ->write("error");
            }
        ]);

        $next = function (Request $request, Response $response) {
            return $response->write("Foo");
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("error", $response->getBody());
    }
}
