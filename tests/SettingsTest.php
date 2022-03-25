<?php

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
     * @dataProvider requestOriginDataProvider
     */
    public function testWildcardOrigin(string $origin, array $allowedOrigins, bool $expected): void
    {
        $requestOriginMock = $this->createMock(ParsedUrl::class);
        $requestOriginMock->method('getOrigin')
            ->willReturn($origin);

        $this->testObject->setRequestAllowedOrigins($allowedOrigins);
        $result = $this->testObject->isRequestOriginAllowed($requestOriginMock);

        $this->assertSame($expected, $result);
    }

    public function requestOriginDataProvider(): iterable
    {
        // Allow subdomain
        $origin = 'http://www.example.com';
        $allowedOrigins = [
            'http://www.example.com' => true,
        ];
        yield [$origin, $allowedOrigins, true];

        // Disallow wrong Subdomain
        $origin = 'http://ws.example.com';
        $allowedOrigins = [
            'http://www.example.com' => true,
        ];
        yield [$origin, $allowedOrigins, false];

        // Allow All
        $origin = 'http://ws.example.com';
        $allowedOrigins = ['*' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow Subdomain Wildcard
        $origin = 'http://ws.example.com';
        $allowedOrigins = ['http://*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow Without Protocol
        $origin = 'http://ws.example.com';
        $allowedOrigins = ['*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow Double Subdomain for Wildcard
        $origin = 'http://a.b.example.com';
        $allowedOrigins = ['*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Don't fall for incorrect position
        $origin = 'http://a.example.com.evil.com';
        $allowedOrigins = ['*.example.com' => true];
        yield [$origin, $allowedOrigins, false];

        // Allow Subdomain in the middle
        $origin = 'a.b.example.com';
        $allowedOrigins = ['a.*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Disallow wrong Subdomain
        $origin = 'b.bc.example.com';
        $allowedOrigins = ['a.*.example.com' => true];
        yield [$origin, $allowedOrigins, false];

        // Correctly handle dots in allowed
        $origin = 'exampleXcom';
        $allowedOrigins = ['example.com' => true];
        yield [$origin, $allowedOrigins, false];

        // Allow subdomain
        $origin = 'test.example.com';
        $allowedOrigins = ['*example*' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow subdomain and domain with one rule
        $origin = 'test.example.com';
        $allowedOrigins = ['*example*' => true];
        yield [$origin, $allowedOrigins, true];
    }
}
