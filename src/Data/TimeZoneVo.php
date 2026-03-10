<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Data;

use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * Immutable value object for a validated timezone identifier
 */
final readonly class TimeZoneVo
{
    public DateTimeZone $dateTimeZone;

    /**
     * @throws InvalidArgumentException If the timezone identifier is not recognized
     */
    public function __construct(public string $identifier)
    {
        try {
            $this->dateTimeZone = new DateTimeZone($identifier);
        } catch (Exception) {
            throw new InvalidArgumentException("\"$identifier\" is not a valid IANA timezone identifier");
        }
    }

    public function __toString(): string
    {
        return $this->identifier;
    }
}
