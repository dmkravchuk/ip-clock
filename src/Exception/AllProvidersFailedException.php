<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown if every provider in the fallback chain has failed
 */
class AllProvidersFailedException extends RuntimeException
{
    private const DEFAULT_MESSAGE = 'All time providers failed to resolve the server time';

    /**
     * @param list<Throwable> $providerErrors Individual errors from each failed provider
     */
    public function __construct(
        public readonly array $providerErrors = [],
        string $message = self::DEFAULT_MESSAGE,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            code: 0,
            previous: $previous,
        );
    }
}
