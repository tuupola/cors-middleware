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

use Generator;
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
    public function testIsRequestOriginAllowed(string $origin, string $allowedOrigins, bool $expected): void
    {
        $this->testObject->setAllowedOrigins([$allowedOrigins]);
        $result = $this->testObject->isRequestOriginAllowed($origin);

        $this->assertSame($expected, $result);
    }

    public function wildcardOriginDataProvider(): Generator
    {
        // Allow subdomain without wildcard
        yield ["https://www.example.com", "https://www.example.com", true];

        // Disallow wrong subdomain
        yield ["https://ws.example.com", "https://www.example.com", false];

        // Allow all
        yield ["https://ws.example.com", "*", true];

        // Allow subdomain wildcard
        yield ["https://ws.example.com", "https://*.example.com", true];

        // Allow without specifying protocol
        yield ["https://ws.example.com", "*.example.com", true];

        // Allow double subdomain for wildcard
        yield ["https://a.b.example.com", "*.example.com", true];

        // Disallow for incorrect domain wildcard
        yield ["https://a.example.com.evil.com", "*.example.com", false];

        // Allow subdomain in the middle
        yield ["a.b.example.com", "a.*.example.com", true];

        // Disallow wrong subdomain
        yield ["b.bc.example.com", "a.*.example.com", false];

        // Correctly handle dots
        yield ["exampleXcom", "example.com", false];

        // Allow subdomain and domain with one rule
        yield ["test.example.com", "*example*", true];
    }
}
