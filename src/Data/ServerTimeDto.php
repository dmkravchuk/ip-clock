<?php

namespace DmKravchuk\IpClock\Data;

use DateTimeImmutable;

/**
 * Resolved server time with its timezone and source provider
 */
final readonly class ServerTimeDto
{
    public function __construct(
        public DateTimeImmutable $dateTime,
        public TimeZoneVo $timeZone,
        public string $provider,
    ) {}
}
