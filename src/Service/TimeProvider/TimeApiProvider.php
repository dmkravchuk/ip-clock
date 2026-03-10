<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service\TimeProvider;

use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
    TimeZoneVo,
};
use DmKravchuk\IpClock\Enum\ApiEndpoint;
use DmKravchuk\IpClock\Exception\TimeResolutionException;
use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Resolves server time via timeapi.io
 * Free to use, no API key required
 */
class TimeApiProvider extends AbstractTimeProvider
{
    private const NAME = 'timeapi.io';
    private const ERROR_MISSING_FIELDS = '[timeapi.io] Response is missing required fields';
    private const ERROR_SERVICE_UNAVAILABLE = '[timeapi.io] Service is unavailable: %s';

    public function getTime(IpAddressVo $ip): ServerTimeDto
    {
        try {
            $data = $this->httpClient->getJson(
                sprintf(ApiEndpoint::TimeApi->value, $ip->value)
            );

            $timezone = $data['timeZone'] ?? null;
            $datetime = $data['dateTime'] ?? null;

            if (empty($timezone) || empty($datetime)) {
                throw new TimeResolutionException(self::ERROR_MISSING_FIELDS);
            }

            return $this->buildResult($timezone, $datetime);
        } catch (GuzzleException $e) {
            throw new TimeResolutionException(
                message: sprintf(self::ERROR_SERVICE_UNAVAILABLE, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * Overrides default parsing to handle timeapi.io's 7-digit milliseconds format
     */
    protected function parseDateTime(string $datetime, TimeZoneVo $timeZone): DateTimeImmutable
    {
        $result = DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.u',
            substr($datetime, 0, 26),
            $timeZone->dateTimeZone
        );

        if ($result === false) {
            throw new \RuntimeException("Cannot parse datetime: $datetime");
        }

        return $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
