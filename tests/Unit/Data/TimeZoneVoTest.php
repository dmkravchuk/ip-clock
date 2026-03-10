<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit\Data;

use DmKravchuk\IpClock\Data\TimeZoneVo;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Test,
};
use PHPUnit\Framework\TestCase;

class TimeZoneVoTest extends TestCase
{
    #[Test]
    #[DataProvider('validTimezoneProvider')]
    public function it_accepts_valid_timezone_identifiers(string $tz): void
    {
        $vo = new TimeZoneVo($tz);

        $this->assertSame($tz, $vo->identifier);
        $this->assertSame($tz, (string) $vo);
        $this->assertInstanceOf(\DateTimeZone::class, $vo->dateTimeZone);
    }

    #[Test]
    #[DataProvider('invalidTimezoneProvider')]
    public function it_rejects_invalid_timezone_identifiers(string $tz): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeZoneVo($tz);
    }

    public static function validTimezoneProvider(): array
    {
        return [
            'UTC'          => ['UTC'],
            'Europe/Kyiv'  => ['Europe/Kyiv'],
            'Asia/Tokyo'   => ['Asia/Tokyo'],
            'America/NY'   => ['America/New_York'],
        ];
    }

    public static function invalidTimezoneProvider(): array
    {
        return [
            'empty string'  => [''],
            'random string' => ['Not/ATimezone'],
        ];
    }
}
