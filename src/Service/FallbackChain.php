<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service;

use DmKravchuk\IpClock\Contract\TimeProviderInterface;
use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
};
use DmKravchuk\IpClock\Exception\{
    AllProvidersFailedException,
    TimeResolutionException,
};
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Tries each time provider in order, falling back to the next one if the current fails
 */
class FallbackChain
{
    /**
     * @param list<TimeProviderInterface> $providers Ordered list of providers to try
     *
     * @throws InvalidArgumentException If no providers are given
     */
    public function __construct(
        private readonly array $providers,
        private readonly LoggerInterface $logger,
    ) {
        if (empty($this->providers)) {
            throw new InvalidArgumentException('At least one time provider must be configured');
        }
    }

    /**
     * Tries each provider in order and returns the first successful result
     *
     * @throws AllProvidersFailedException If every provider fails
     */
    public function resolve(IpAddressVo $ip): ServerTimeDto
    {
        $errors = [];

        foreach ($this->providers as $provider) {
            try {
                return $provider->getTime($ip);
            } catch (TimeResolutionException $e) {
                $this->logger->warning('Time provider failed, trying next one', [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage(),
                ]);

                $errors[] = $e;
            }
        }

        $this->logger->error('All time providers failed', [
            'providers' => array_map(fn($p) => $p->getName(), $this->providers),
        ]);

        throw new AllProvidersFailedException(providerErrors: $errors);
    }
}
