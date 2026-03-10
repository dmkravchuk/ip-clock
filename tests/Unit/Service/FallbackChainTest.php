<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit\Service;

use DateTimeImmutable;
use DmKravchuk\IpClock\Contract\TimeProviderInterface;
use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
    TimeZoneVo,
};
use DmKravchuk\IpClock\Exception\{
    AllProvidersFailedException,
    TimeResolutionException,
};
use DmKravchuk\IpClock\Service\FallbackChain;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FallbackChainTest extends TestCase
{
    private const TEST_IP = '8.8.8.8';

    private IpAddressVo $ip;

    protected function setUp(): void
    {
        $this->ip = new IpAddressVo(self::TEST_IP);
    }

    #[Test]
    public function it_returns_result_from_first_provider_when_it_succeeds(): void
    {
        $expected = $this->makeServerTimeDto('primary');
        $chain = new FallbackChain([
            $this->makeSuccessfulProvider('primary', $expected),
            $this->makeFailingProvider('fallback'),
        ], new NullLogger());

        $result = $chain->resolve($this->ip);

        $this->assertSame('primary', $result->provider);
    }

    #[Test]
    public function it_falls_back_to_next_provider_when_first_fails(): void
    {
        $expected = $this->makeServerTimeDto('fallback');
        $chain = new FallbackChain([
            $this->makeFailingProvider('primary'),
            $this->makeSuccessfulProvider('fallback', $expected),
        ], new NullLogger());

        $result = $chain->resolve($this->ip);

        $this->assertSame('fallback', $result->provider);
    }

    #[Test]
    public function it_falls_back_to_third_provider_when_first_two_fail(): void
    {
        $expected = $this->makeServerTimeDto('third');
        $chain = new FallbackChain([
            $this->makeFailingProvider('primary'),
            $this->makeFailingProvider('fallback-1'),
            $this->makeSuccessfulProvider('third', $expected),
        ], new NullLogger());

        $result = $chain->resolve($this->ip);

        $this->assertSame('third', $result->provider);
    }

    #[Test]
    public function it_throws_when_all_providers_fail(): void
    {
        $chain = new FallbackChain([
            $this->makeFailingProvider('primary'),
            $this->makeFailingProvider('fallback'),
        ], new NullLogger());

        $this->expectException(AllProvidersFailedException::class);

        $chain->resolve($this->ip);
    }

    #[Test]
    public function it_includes_each_provider_error_in_the_exception(): void
    {
        $chain = new FallbackChain([
            $this->makeFailingProvider('primary'),
            $this->makeFailingProvider('fallback'),
        ], new NullLogger());

        try {
            $chain->resolve($this->ip);
            $this->fail('Expected AllProvidersFailedException was not thrown');
        } catch (AllProvidersFailedException $e) {
            $this->assertCount(2, $e->providerErrors);
        }
    }

    #[Test]
    public function it_throws_when_constructed_with_no_providers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FallbackChain([], new NullLogger());
    }

    private function makeSuccessfulProvider(string $name, ServerTimeDto $result): TimeProviderInterface
    {
        $provider = $this->createMock(TimeProviderInterface::class);
        $provider->method('getName')->willReturn($name);
        $provider->method('getTime')->willReturn($result);

        return $provider;
    }

    private function makeFailingProvider(string $name): TimeProviderInterface
    {
        $provider = $this->createMock(TimeProviderInterface::class);
        $provider->method('getName')->willReturn($name);
        $provider->method('getTime')->willThrowException(
            new TimeResolutionException("[$name] Service unavailable")
        );

        return $provider;
    }

    private function makeServerTimeDto(string $provider): ServerTimeDto
    {
        $tz = new TimeZoneVo('UTC');

        return new ServerTimeDto(
            dateTime: new DateTimeImmutable('now', $tz->dateTimeZone),
            timeZone: $tz,
            provider: $provider,
        );
    }
}
