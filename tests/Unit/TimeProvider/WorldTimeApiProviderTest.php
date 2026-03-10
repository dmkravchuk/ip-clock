<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit\TimeProvider;

use DmKravchuk\IpClock\Data\IpAddressVo;
use DmKravchuk\IpClock\Exception\TimeResolutionException;
use DmKravchuk\IpClock\Service\HttpClient;
use DmKravchuk\IpClock\Service\TimeProvider\WorldTimeApiProvider;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class WorldTimeApiProviderTest extends TestCase
{
    private const TEST_IP = '8.8.8.8';

    private IpAddressVo $ip;

    protected function setUp(): void
    {
        $this->ip = new IpAddressVo(self::TEST_IP);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_returns_server_time_dto_on_success(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willReturn([
            'timezone' => 'Europe/Kyiv',
            'datetime' => '2026-03-10T14:00:00+02:00',
        ]);

        $provider = new WorldTimeApiProvider($httpClient, new NullLogger());
        $result = $provider->getTime($this->ip);

        $this->assertSame('worldtimeapi.org', $result->provider);
        $this->assertSame('Europe/Kyiv', $result->timeZone->identifier);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_throws_when_response_is_missing_fields(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willReturn([]);

        $provider = new WorldTimeApiProvider($httpClient, new NullLogger());

        $this->expectException(TimeResolutionException::class);

        $provider->getTime($this->ip);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_throws_when_service_is_unavailable(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willThrowException(
            new ConnectException('Connection timed out', new Request('GET', '/'))
        );

        $provider = new WorldTimeApiProvider($httpClient, new NullLogger());

        $this->expectException(TimeResolutionException::class);

        $provider->getTime($this->ip);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_throws_when_timezone_is_invalid(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willReturn([
            'timezone' => 'Invalid/Timezone',
            'datetime' => '2026-03-10T14:00:00+02:00',
        ]);

        $provider = new WorldTimeApiProvider($httpClient, new NullLogger());

        $this->expectException(TimeResolutionException::class);

        $provider->getTime($this->ip);
    }
}
