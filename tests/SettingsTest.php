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
     * @dataProvider wildcardOriginDataProvider
     */
    public function testIsRequestOriginAllowed(string $origin, array $allowedOrigins, bool $expected): void
    {
        $requestOriginMock = $this->createMock(ParsedUrl::class);
        $requestOriginMock->method('getOrigin')
            ->willReturn($origin);

        $this->testObject->setRequestAllowedOrigins($allowedOrigins);
        $result = $this->testObject->isRequestOriginAllowed($requestOriginMock);

        $this->assertSame($expected, $result);
    }

    public function wildcardOriginDataProvider(): iterable
    {
        // Allow subdomain without wildcard
        $origin = 'https://www.example.com';
        $allowedOrigins = [
            'https://www.example.com' => true,
        ];
        yield [$origin, $allowedOrigins, true];

        // Disallow wrong subdomain
        $origin = 'https://ws.example.com';
        $allowedOrigins = [
            'https://www.example.com' => true,
        ];
        yield [$origin, $allowedOrigins, false];

        // Allow all
        $origin = 'https://ws.example.com';
        $allowedOrigins = ['*' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow subdomain wildcard
        $origin = 'https://ws.example.com';
        $allowedOrigins = ['https://*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow without specifying protocol
        $origin = 'https://ws.example.com';
        $allowedOrigins = ['*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Allow double subdomain for wildcard
        $origin = 'https://a.b.example.com';
        $allowedOrigins = ['*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Disallow for incorrect domain wildcard
        $origin = 'https://a.example.com.evil.com';
        $allowedOrigins = ['*.example.com' => true];
        yield [$origin, $allowedOrigins, false];

        // Allow subdomain in the middle
        $origin = 'a.b.example.com';
        $allowedOrigins = ['a.*.example.com' => true];
        yield [$origin, $allowedOrigins, true];

        // Disallow wrong subdomain
        $origin = 'b.bc.example.com';
        $allowedOrigins = ['a.*.example.com' => true];
        yield [$origin, $allowedOrigins, false];

        // Correctly handle dots
        $origin = 'exampleXcom';
        $allowedOrigins = ['example.com' => true];
        yield [$origin, $allowedOrigins, false];

        // Allow subdomain and domain with one rule
        $origin = 'test.example.com';
        $allowedOrigins = ['*example*' => true];
        yield [$origin, $allowedOrigins, true];
    }
}
