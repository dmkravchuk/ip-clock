<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock;

use DateTimeImmutable;
use DateTimeZone;
use DmKravchuk\IpClock\Contract\IpResolverInterface;
use DmKravchuk\IpClock\Exception\{
    AllProvidersFailedException,
    IpResolutionException,
};
use DmKravchuk\IpClock\Service\FallbackChain;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * PSR-20 compliant clock that returns the correct time
 * based on the server's external IP address and timezone
 */
readonly class Clock implements ClockInterface
{
    public function __construct(
        private IpResolverInterface $ipResolver,
        private FallbackChain $chain,
        private LoggerInterface $logger,
        private ?string $ip = null,
    ) {}

    /**
     * Returns current time in the server's local timezone
     * Falls back to UTC if IP resolution or all providers fail
     */
    public function now(): DateTimeImmutable
    {
        try {
            $ipVo = $this->ipResolver->resolve($this->ip);

            return $this->chain->resolve($ipVo)->dateTime;
        } catch (IpResolutionException $e) {
            $this->logger->critical('IP resolution failed, falling back to UTC', [
                'error' => $e->getMessage(),
            ]);
        } catch (AllProvidersFailedException $e) {
            $this->logger->critical('All providers failed, falling back to UTC', [
                'errors' => array_map(fn($err) => $err->getMessage(), $e->providerErrors),
            ]);
        }

        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
