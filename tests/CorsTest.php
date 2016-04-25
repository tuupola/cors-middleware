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

namespace Tuupola\Middleware\Test;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

use Tuupola\Middleware\Cors;

class CorsTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    public function testShouldReturn200ByDefault()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;
        $cors = new Cors([]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
        //$this->assertEquals("", $response->getBody());
    }

    public function testShouldHaveCorsHeaders()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Origin", "http://www.example.com");

        $response = new Response;
        $cors = new Cors([
            "origin" => "*",
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals("http://www.example.com", $response->getHeaderLine("Access-Control-Allow-Origin"));
        $this->assertEquals("true", $response->getHeaderLine("Access-Control-Allow-Credentials"));
        $this->assertEquals("Origin", $response->getHeaderLine("Vary"));
        $this->assertEquals("Authorization,Etag", $response->getHeaderLine("Access-Control-Expose-Headers"));
    }

    public function testShouldReturn401WithWrongOrigin()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Origin", "http://www.foo.com");

        $response = new Response;
        $cors = new Cors([
            "origin" => "http://www.example.com",
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn200WithCorrectOrigin()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Origin", "http://mobile.example.com");

        $response = new Response;
        $cors = new Cors([
            "origin" => ["http://www.example.com", "http://mobile.example.com"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldReturn401WithWrongMethod()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = new Response;
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn401WithWrongHeader()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "X-Nosuch")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = new Response;
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400,
            "error" => function ($request, $response, $arguments) {
                return "ignored";
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn200WithProperPreflightRequest()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = new Response;
        $cors = new Cors([
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldCallError()
    {
        $request = (new Request())
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "X-Nosuch")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = new Response;
        $logger = new NullLogger;
        $cors = new Cors([
            "logger" => $logger,
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400,
            "error" => function ($request, $response, $arguments) {
                $response->getBody()->write("Error");
                return $response;
            }
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("Error", $response->getBody());
    }

    public function testShouldSetAndGetError()
    {
        $cors = new Cors([]);
        $cors->setError(function () {
            return "error";
        });
        $error = $cors->getError();
        $this->assertEquals("error", $error());
    }

    public function testShouldSetAndGetLogger()
    {
        $logger = new NullLogger;
        $cors = new Cors([]);
        $cors->setLogger($logger);
        $this->assertInstanceOf("Psr\Log\NullLogger", $cors->getLogger());
    }
}
