<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit\IpResolver;

use DmKravchuk\IpClock\Exception\IpResolutionException;
use DmKravchuk\IpClock\Service\HttpClient;
use DmKravchuk\IpClock\Service\IpResolver\ExternalIpResolver;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ExternalIpResolverTest extends TestCase
{
    private const DETECTED_IP = '89.28.92.18';
    private const PROVIDED_IP = '8.8.8.8';

    /**
     * @throws Exception
     */
    #[Test]
    public function it_returns_provided_ip_when_explicitly_given(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->never())->method('getJson');

        $resolver = new ExternalIpResolver($httpClient, new NullLogger());
        $result = $resolver->resolve(self::PROVIDED_IP);

        $this->assertSame(self::PROVIDED_IP, $result->value);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_detects_external_ip_when_none_provided(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willReturn(['ip' => self::DETECTED_IP]);

        $resolver = new ExternalIpResolver($httpClient, new NullLogger());
        $result = $resolver->resolve();

        $this->assertSame(self::DETECTED_IP, $result->value);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_throws_when_provided_ip_is_invalid(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $resolver = new ExternalIpResolver($httpClient, new NullLogger());

        $this->expectException(IpResolutionException::class);

        $resolver->resolve('not-an-ip');
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_throws_when_ip_field_is_missing_in_response(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willReturn([]);

        $resolver = new ExternalIpResolver($httpClient, new NullLogger());

        $this->expectException(IpResolutionException::class);

        $resolver->resolve();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_throws_when_ip_detection_service_is_unavailable(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('getJson')->willThrowException(
            new ConnectException('Connection timed out', new Request('GET', '/'))
        );

        $resolver = new ExternalIpResolver($httpClient, new NullLogger());

        $this->expectException(IpResolutionException::class);

        $resolver->resolve();
    }
}
