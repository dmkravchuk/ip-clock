<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Config;

/**
 * Holds configuration values for the clock package
 * Use fromEnv() to build config from environment variables
 */
readonly class ClockConfig
{
    public function __construct(
        public string $ipGeolocationApiKey = '',
    ) {}

    /**
     * Builds config by reading values from environment variables
     */
    public static function fromEnv(): self
    {
        return new self(
            ipGeolocationApiKey: getenv('IPGEOLOCATION_API_KEY') ?: '',
        );
    }
}
