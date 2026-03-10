<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock;

use DmKravchuk\IpClock\Config\ClockConfig;
use DmKravchuk\IpClock\Service\{
    FallbackChain,
    HttpClient,
};
use DmKravchuk\IpClock\Service\IpResolver\ExternalIpResolver;
use DmKravchuk\IpClock\Service\TimeProvider\{
    IpGeolocationProvider,
    TimeApiProvider,
    WorldTimeApiProvider,
};
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Creates a fully wired Clock instance with sensible defaults
 *
 * Usage:
 * ```php
 * // Auto-detect server IP
 * $clock = ClockFactory::create();
 *
 * // Explicit IP
 * $clock = ClockFactory::create(ip: '8.8.8.8');
 *
 * // With custom logger
 * $clock = ClockFactory::create(logger: $myLogger);
 * ```
 */
class ClockFactory
{
    /**
     * Creates a Clock with a three-provider fallback chain
     *
     * @param string|null $ip Optional explicit IP address, auto-detected if null
     * @param LoggerInterface|null $logger Custom logger, uses NullLogger if not provided
     * @param ClockConfig|null $config Package config, reads from env if not provided
     */
    public static function create(
        ?string $ip = null,
        ?LoggerInterface $logger = null,
        ?ClockConfig $config = null,
    ): Clock {
        $logger = $logger ?? new NullLogger();
        $config = $config ?? ClockConfig::fromEnv();
        $httpClient = new HttpClient(new Client());

        $chain = new FallbackChain([
            new TimeApiProvider($httpClient, $logger),
            new WorldTimeApiProvider($httpClient, $logger),
            new IpGeolocationProvider($httpClient, $logger, $config->ipGeolocationApiKey),
        ], $logger);

        return new Clock(
            ipResolver: new ExternalIpResolver($httpClient, $logger),
            chain: $chain,
            logger: $logger,
            ip: $ip,
        );
    }
}
