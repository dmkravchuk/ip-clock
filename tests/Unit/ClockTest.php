<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit;

use DateTimeImmutable;
use DmKravchuk\IpClock\Clock;
use DmKravchuk\IpClock\Contract\IpResolverInterface;
use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
    TimeZoneVo,
};
use DmKravchuk\IpClock\Exception\AllProvidersFailedException;
use DmKravchuk\IpClock\Exception\IpResolutionException;
use DmKravchuk\IpClock\Service\FallbackChain;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Log\NullLogger;

class ClockTest extends TestCase
{
    private const TEST_IP = '8.8.8.8';

    /**
     * @throws Exception
     */
    #[Test]
    public function it_implements_psr20_clock_interface(): void
    {
        $clock = $this->makeClock(timezone: 'Europe/Kyiv');

        $this->assertInstanceOf(ClockInterface::class, $clock);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_returns_date_time_immutable(): void
    {
        $clock = $this->makeClock(timezone: 'Europe/Kyiv');

        $this->assertInstanceOf(DateTimeImmutable::class, $clock->now());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_returns_time_in_resolved_timezone(): void
    {
        $clock = $this->makeClock(timezone: 'Asia/Tokyo');

        $this->assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_falls_back_to_utc_when_ip_resolution_fails(): void
    {
        $ipResolver = $this->createMock(IpResolverInterface::class);
        $ipResolver->method('resolve')->willThrowException(
            new IpResolutionException('No network')
        );

        $chain = $this->createMock(FallbackChain::class);

        $clock = new Clock($ipResolver, $chain, new NullLogger());

        $now = $clock->now();

        $this->assertSame('UTC', $now->getTimezone()->getName());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_falls_back_to_utc_when_all_providers_fail(): void
    {
        $ipResolver = $this->createMock(IpResolverInterface::class);
        $ipResolver->method('resolve')->willReturn(new IpAddressVo(self::TEST_IP));

        $chain = $this->createMock(FallbackChain::class);
        $chain->method('resolve')->willThrowException(
            new AllProvidersFailedException()
        );

        $clock = new Clock($ipResolver, $chain, new NullLogger());

        $now = $clock->now();

        $this->assertSame('UTC', $now->getTimezone()->getName());
    }

    /**
     * @throws Exception
     */
    private function makeClock(string $timezone): Clock
    {
        $tz = new TimeZoneVo($timezone);
        $ipVo = new IpAddressVo(self::TEST_IP);

        $serverTime = new ServerTimeDto(
            dateTime: new DateTimeImmutable('now', $tz->dateTimeZone),
            timeZone: $tz,
            provider: 'mock',
        );

        $ipResolver = $this->createMock(IpResolverInterface::class);
        $ipResolver->method('resolve')->willReturn($ipVo);

        $chain = $this->createMock(FallbackChain::class);
        $chain->method('resolve')->willReturn($serverTime);

        return new Clock($ipResolver, $chain, new NullLogger());
    }
}
