<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Feature;

use DateTimeImmutable;
use DmKravchuk\IpClock\Clock;
use DmKravchuk\IpClock\Contract\IpResolverInterface;
use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
    TimeZoneVo,
};
use DmKravchuk\IpClock\Exception\TimeResolutionException;
use DmKravchuk\IpClock\Service\{
    FallbackChain,
    HttpClient,
};
use DmKravchuk\IpClock\Service\IpResolver\ExternalIpResolver;
use PHPUnit\Framework\MockObject\Exception;
use DmKravchuk\IpClock\Service\TimeProvider\{
    TimeApiProvider,
    WorldTimeApiProvider,
};
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ClockFeatureTest extends TestCase
{
    private const SERVER_IP = '89.28.92.18';
    private const SERVER_TIMEZONE = 'Europe/Chisinau';
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s.u';
    private const PROVIDER_PRIMARY = 'timeapi.io';
    private const PROVIDER_FALLBACK = 'worldtimeapi.org';

    #[Test]
    public function it_resolves_time_via_primary_provider(): void
    {
        $clock = $this->makeClockWithResponses(
            timeApiResponse: [
                'timeZone' => self::SERVER_TIMEZONE,
                'dateTime' => (new DateTimeImmutable())->format(self::DATETIME_FORMAT).'0',
            ],
        );

        $now = $clock->now();

        $this->assertSame(self::SERVER_TIMEZONE, $now->getTimezone()->getName());
        $this->assertInstanceOf(DateTimeImmutable::class, $now);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_falls_back_when_primary_provider_fails(): void
    {
        $logger = new NullLogger();
        $httpClient = $this->createMock(HttpClient::class);

        $ipResolver = new ExternalIpResolver($httpClient, $logger);
        $ip = new IpAddressVo(self::SERVER_IP);

        $tz = new TimeZoneVo(self::SERVER_TIMEZONE);
        $serverTime = new ServerTimeDto(
            dateTime: new DateTimeImmutable('now', $tz->dateTimeZone),
            timeZone: $tz,
            provider: 'worldtimeapi.org',
        );

        $primaryProvider = $this->createMock(TimeApiProvider::class);
        $primaryProvider->method('getName')->willReturn(self::PROVIDER_PRIMARY);
        $primaryProvider->method('getTime')->willThrowException(
            new TimeResolutionException('[timeapi.io] Service unavailable')
        );

        $fallbackProvider = $this->createMock(WorldTimeApiProvider::class);
        $fallbackProvider->method('getName')->willReturn(self::PROVIDER_FALLBACK);
        $fallbackProvider->method('getTime')->willReturn($serverTime);

        $ipResolver = $this->createMock(IpResolverInterface::class);
        $ipResolver->method('resolve')->willReturn($ip);

        $chain = new FallbackChain([$primaryProvider, $fallbackProvider], $logger);
        $clock = new Clock($ipResolver, $chain, $logger);

        $now = $clock->now();

        $this->assertSame(self::SERVER_TIMEZONE, $now->getTimezone()->getName());
    }

    #[Test]
    public function it_returns_utc_when_all_providers_fail(): void
    {
        $logger = new NullLogger();

        $ip = new IpAddressVo(self::SERVER_IP);

        $ipResolver = $this->createMock(IpResolverInterface::class);
        $ipResolver->method('resolve')->willReturn($ip);

        $primaryProvider = $this->createMock(TimeApiProvider::class);
        $primaryProvider->method('getName')->willReturn(self::PROVIDER_PRIMARY);
        $primaryProvider->method('getTime')->willThrowException(
            new TimeResolutionException('[timeapi.io] Service unavailable')
        );

        $fallbackProvider = $this->createMock(WorldTimeApiProvider::class);
        $fallbackProvider->method('getName')->willReturn(self::PROVIDER_FALLBACK);
        $fallbackProvider->method('getTime')->willThrowException(
            new TimeResolutionException('[worldtimeapi.org] Service unavailable')
        );

        $chain = new FallbackChain([$primaryProvider, $fallbackProvider], $logger);
        $clock = new Clock($ipResolver, $chain, $logger);

        $now = $clock->now();

        $this->assertSame('UTC', $now->getTimezone()->getName());
    }

    private function makeClockWithResponses(array $timeApiResponse): Clock
    {
        $logger = new NullLogger();

        $mock = new MockHandler([
            new Response(200, [], json_encode(['ip' => self::SERVER_IP])),
            new Response(200, [], json_encode($timeApiResponse)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($guzzleClient);

        $chain = new FallbackChain([
            new TimeApiProvider($httpClient, $logger),
        ], $logger);

        return new Clock(
            ipResolver: new ExternalIpResolver($httpClient, $logger),
            chain: $chain,
            logger: $logger,
        );
    }
}
