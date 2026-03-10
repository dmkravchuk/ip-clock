<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Contract;

use DmKravchuk\IpClock\Data\IpAddressVo;
use DmKravchuk\IpClock\Exception\IpResolutionException;

/**
 * Contract for resolving the server's external IP address
 */
interface IpResolverInterface
{
    /**
     * Resolves the external IP address
     *
     * If $ip is provided, validates and returns it directly
     * If null, auto-detects the server's external IP
     *
     * @throws IpResolutionException When the IP cannot be resolved
     */
    public function resolve(?string $ip = null): IpAddressVo;
}
