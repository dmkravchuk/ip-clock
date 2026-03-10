<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Contract;

use DmKravchuk\IpClock\Data\IpAddressVo;
use DmKravchuk\IpClock\Data\ServerTimeDto;
use DmKravchuk\IpClock\Exception\TimeResolutionException;

/**
 * Contract for time providers that resolve server time by IP address
 */
interface TimeProviderInterface
{
    /**
     * Fetches current time for the given IP address
     *
     * @throws TimeResolutionException When time cannot be resolved
     */
    public function getTime(IpAddressVo $ip): ServerTimeDto;

    /**
     * Returns provider name used in logs and error messages
     */
    public function getName(): string;
}
