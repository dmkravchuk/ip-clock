<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service\TimeProvider;

use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
};
use DmKravchuk\IpClock\Enum\ApiEndpoint;
use DmKravchuk\IpClock\Exception\TimeResolutionException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Resolves server time via worldtimeapi.org
 * Free to use, no API key required
 */
class WorldTimeApiProvider extends AbstractTimeProvider
{
    private const NAME = 'worldtimeapi.org';
    private const ERROR_MISSING_FIELDS = '[worldtimeapi.org] Response is missing required fields';
    private const ERROR_SERVICE_UNAVAILABLE = '[worldtimeapi.org] Service is unavailable: %s';

    public function getTime(IpAddressVo $ip): ServerTimeDto
    {
        try {
            $data = $this->httpClient->getJson(
                sprintf(ApiEndpoint::WorldTimeApi->value, $ip->value)
            );

            $timezone = $data['timezone'] ?? null;
            $datetime = $data['datetime'] ?? null;

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

    public function getName(): string
    {
        return self::NAME;
    }
}
