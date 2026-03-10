<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service\TimeProvider;

use DmKravchuk\IpClock\Contract\TimeProviderInterface;
use DmKravchuk\IpClock\Data\{
    ServerTimeDto,
    TimeZoneVo,
};
use DmKravchuk\IpClock\Exception\TimeResolutionException;
use DmKravchuk\IpClock\Service\HttpClient;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Base class for time providers with shared parsing and error handling logic
 */
abstract class AbstractTimeProvider implements TimeProviderInterface
{
    private const ERROR_INVALID_TIMEZONE = '[%s] Invalid timezone in response: %s';
    private const ERROR_INVALID_DATETIME = '[%s] Invalid datetime format in response: %s';

    public function __construct(
        protected readonly HttpClient $httpClient,
        protected readonly LoggerInterface $logger,
    ) {}

    /**
     * Builds a ServerTimeDto from raw timezone and datetime strings
     *
     * @throws TimeResolutionException If timezone or datetime is invalid
     */
    protected function buildResult(string $timezone, string $datetime): ServerTimeDto
    {
        try {
            $timeZone = new TimeZoneVo($timezone);
        } catch (InvalidArgumentException $e) {
            throw new TimeResolutionException(
                message: sprintf(self::ERROR_INVALID_TIMEZONE, $this->getName(), $e->getMessage()),
                previous: $e,
            );
        }

        try {
            $dateTime = $this->parseDateTime($datetime, $timeZone);
        } catch (\Exception $e) {
            throw new TimeResolutionException(
                message: sprintf(self::ERROR_INVALID_DATETIME, $this->getName(), $e->getMessage()),
                previous: $e,
            );
        }

        $this->logger->info('Time resolved', [
            'provider' => $this->getName(),
            'timezone' => $timezone,
        ]);

        return new ServerTimeDto(
            dateTime: $dateTime->setTimezone($timeZone->dateTimeZone),
            timeZone: $timeZone,
            provider: $this->getName(),
        );
    }

    /**
     * Parses datetime string — override in subclass if API returns non-standard format
     *
     * @throws \Exception If datetime cannot be parsed
     */
    protected function parseDateTime(string $datetime, TimeZoneVo $timeZone): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, $timeZone->dateTimeZone);
    }
}
