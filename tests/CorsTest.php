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

namespace Tuupola\Middleware;

use Equip\Dispatch\MiddlewareCollection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Http\Factory\ServerRequestFactory;
use Tuupola\Http\Factory\UriFactory;

class CorsTest extends TestCase
{

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    public function testShouldReturn200ByDefault()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("GET", "https://example.com/api");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
        //$this->assertEquals("", $response->getBody());
    }

    public function testShouldHaveCorsHeaders()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => "*",
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $request = (new ServerRequestFactory())
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Origin", "http://www.foo.com");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => ["http://www.example.com"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShouldReturn200WithCorrectOrigin()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Origin", "http://mobile.example.com");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => ["http://www.example.com", "http://mobile.example.com"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldReturn401WithWrongMethod()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => "*",
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

    public function testShouldReturn401WithWrongMethodFromFunction()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => "*",
            "methods" => function ($request) {
                return ["GET", "POST", "DELETE"];
            },
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

    public function testShouldReturn401WithWrongMethodFromInvokableClass()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => "*",
            "methods" => new TestMethodsHandler(),
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


    public function testShouldReturn200WithCorrectMethodFromFunction()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "DELETE");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => ["*"],
            "methods" => function ($request) {
                return ["GET", "POST", "DELETE"];
            },
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

    public function testShouldReturn200WithCorrectMethodFromInvokableClass()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "DELETE");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => ["*"],
            "methods" => new TestMethodsHandler(),
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

    public function testShouldReturn200WithCorrectMethodUsingArrayNotation()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "DELETE");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => ["*"],
            "methods" => [TestMethodsHandler::class, "methods"],
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

    public function testShouldReturn401WithWrongHeader()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "X-Nosuch")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
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
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "Authorization")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
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

    public function testShouldReturn200WithNoCorsHeaders()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Origin", "https://example.com");

        $response = (new ResponseFactory())->createResponse();
        $cors = new CorsMiddleware([
            "origin" => [],
            "origin.server" => "https://example.com"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getHeaderLine("Access-Control-Allow-Origin"));
    }

    public function testShouldCallAnonymousErrorFunction()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "X-Nosuch")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $logger = new NullLogger();
        $cors = new CorsMiddleware([
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

    public function testShouldCallInvokableErrorClass()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "X-Nosuch")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $logger = new NullLogger();
        $cors = new CorsMiddleware([
            "logger" => $logger,
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400,
            "error" => new TestErrorHandler()
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(402, $response->getStatusCode());
        $this->assertEquals(TestErrorHandler::class, $response->getBody());
    }

    public function testShouldCallArrayNotationError()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("OPTIONS", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com")
            ->withHeader("Access-Control-Request-Headers", "X-Nosuch")
            ->withHeader("Access-Control-Request-Method", "PUT");

        $response = (new ResponseFactory())->createResponse();
        $logger = new NullLogger();
        $cors = new CorsMiddleware([
            "logger" => $logger,
            "origin" => ["*"],
            "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
            "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
            "headers.expose" => ["Authorization", "Etag"],
            "credentials" => true,
            "cache" => 86400,
            "error" => [TestErrorHandler::class, "error"]
        ]);

        $next = function (Request $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $cors($request, $response, $next);
        $this->assertEquals(418, $response->getStatusCode());
        $this->assertEquals(TestErrorHandler::class, $response->getBody());
    }

    public function testShouldHandlePsr15()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Origin", "http://www.example.com");

        $default = function (ServerRequestInterface $request) {
            $response = (new ResponseFactory())->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new CorsMiddleware([
                "origin" => ["*"],
                "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
                "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
                "headers.expose" => ["Authorization", "Etag"],
                "credentials" => true,
                "cache" => 86400
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals("http://www.example.com", $response->getHeaderLine("Access-Control-Allow-Origin"));
        $this->assertEquals("true", $response->getHeaderLine("Access-Control-Allow-Credentials"));
        $this->assertEquals("Origin", $response->getHeaderLine("Vary"));
        $this->assertEquals("Authorization,Etag", $response->getHeaderLine("Access-Control-Expose-Headers"));
        $this->assertEquals("Success", $response->getBody());
    }
}
