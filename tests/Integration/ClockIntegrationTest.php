<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Integration;

use DateTimeImmutable;
use DmKravchuk\IpClock\ClockFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ClockIntegrationTest extends TestCase
{
    private const TEST_IP = '8.8.8.8';

    #[Test]
    public function it_returns_valid_datetime_immutable(): void
    {
        $clock = ClockFactory::create();

        $this->assertInstanceOf(DateTimeImmutable::class, $clock->now());
    }

    #[Test]
    public function it_implements_psr20_interface(): void
    {
        $clock = ClockFactory::create();

        $this->assertInstanceOf(ClockInterface::class, $clock);
    }

    #[Test]
    public function it_returns_valid_timezone(): void
    {
        $clock = ClockFactory::create();
        $now = $clock->now();

        $this->assertNotEmpty($now->getTimezone()->getName());
    }

    #[Test]
    public function it_returns_correct_time_for_explicit_ip(): void
    {
        $clock = ClockFactory::create(ip: self::TEST_IP);
        $now = $clock->now();

        $this->assertSame('America/Los_Angeles', $now->getTimezone()->getName());
    }
}
