<?php

/*

Copyright (c) 2022 Pavlo Mikhailidi

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

declare(strict_types=1);

namespace Tuupola\Middleware;

use Neomerx\Cors\Http\ParsedUrl;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    /**
     * @var Settings
     */
    private $testObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testObject = new Settings();
    }

    /**
     * @dataProvider wildcardOriginDataProvider
     */
    public function testIsRequestOriginAllowed(string $origin, array $allowedOrigins, bool $expected): void
    {
        $requestOriginMock = $this->createMock(ParsedUrl::class);
        $requestOriginMock->method("getOrigin")
            ->willReturn($origin);

        $this->testObject->setRequestAllowedOrigins($allowedOrigins);
        $result = $this->testObject->isRequestOriginAllowed($requestOriginMock);

        $this->assertSame($expected, $result);
    }

    public function wildcardOriginDataProvider(): iterable
    {
        // Allow subdomain without wildcard
        $origin = "https://www.example.com";
        $allowedOrigins = ["https://www.example.com" => true];
        yield [$origin, $allowedOrigins, true];

        // Disallow wrong subdomain
        $origin = "https://ws.example.com";
        $allowedOrigins = ["https://www.example.com" => true];
        yield [$origin, $allowedOrigins, false];

        // Allow all
        $origin = "https://ws.example.com";
        $allowedOrigins = ["*" => true];
        yield [$origin, $allowedOrigins, true];

        // Allow subdomain wildcard
        $origin = "https://ws.example.com";
        $allowedOrigins = ["https://*.example.com" => true];
        yield [$origin, $allowedOrigins, true];

        // Allow without specifying protocol
        $origin = "https://ws.example.com";
        $allowedOrigins = ["*.example.com" => true];
        yield [$origin, $allowedOrigins, true];

        // Allow double subdomain for wildcard
        $origin = "https://a.b.example.com";
        $allowedOrigins = ["*.example.com" => true];
        yield [$origin, $allowedOrigins, true];

        // Disallow for incorrect domain wildcard
        $origin = "https://a.example.com.evil.com";
        $allowedOrigins = ["*.example.com" => true];
        yield [$origin, $allowedOrigins, false];

        // Allow subdomain in the middle
        $origin = "a.b.example.com";
        $allowedOrigins = ["a.*.example.com" => true];
        yield [$origin, $allowedOrigins, true];

        // Disallow wrong subdomain
        $origin = "b.bc.example.com";
        $allowedOrigins = ["a.*.example.com" => true];
        yield [$origin, $allowedOrigins, false];

        // Correctly handle dots
        $origin = "exampleXcom";
        $allowedOrigins = ["example.com" => true];
        yield [$origin, $allowedOrigins, false];

        // Allow subdomain and domain with one rule
        $origin = "test.example.com";
        $allowedOrigins = ["*example*" => true];
        yield [$origin, $allowedOrigins, true];
    }
}
