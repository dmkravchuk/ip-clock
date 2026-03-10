<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Data;

use InvalidArgumentException;

/**
 * Immutable value object representing a validated IP address
 */
final readonly class IpAddressVo
{
    /**
     * @throws InvalidArgumentException If the given value is not a valid IP address
     */
    public function __construct(public string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException("\"$value\" is not a valid IP address");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
