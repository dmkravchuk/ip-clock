<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit\Data;

use DateTimeImmutable;
use DmKravchuk\IpClock\Data\ServerTimeDto;
use DmKravchuk\IpClock\Data\TimeZoneVo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServerTimeDtoTest extends TestCase
{
    #[Test]
    public function it_holds_resolved_time_data(): void
    {
        $tz = new TimeZoneVo('UTC');
        $dateTime = new DateTimeImmutable('now', $tz->dateTimeZone);

        $dto = new ServerTimeDto(
            dateTime: $dateTime,
            timeZone: $tz,
            provider: 'test-provider',
        );

        $this->assertSame($dateTime, $dto->dateTime);
        $this->assertSame($tz, $dto->timeZone);
        $this->assertSame('test-provider', $dto->provider);
    }
}
