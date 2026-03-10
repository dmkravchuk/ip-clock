<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service\TimeProvider;

use DmKravchuk\IpClock\Data\{
    IpAddressVo,
    ServerTimeDto,
};
use DmKravchuk\IpClock\Enum\ApiEndpoint;
use DmKravchuk\IpClock\Exception\TimeResolutionException;
use DmKravchuk\IpClock\Service\HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Resolves server time via ipgeolocation.io
 * Requires an API key — create a free account at https://ipgeolocation.io
 */
class IpGeolocationProvider extends AbstractTimeProvider
{
    private const NAME = 'ipgeolocation.io';
    private const ERROR_MISSING_FIELDS = '[ipgeolocation.io] Response is missing required fields';
    private const ERROR_SERVICE_UNAVAILABLE = '[ipgeolocation.io] Service is unavailable: %s';
    private const ERROR_MISSING_API_KEY = '[ipgeolocation.io] API key is not configured';

    public function __construct(
        HttpClient $httpClient,
        LoggerInterface $logger,
        private readonly string $apiKey,
    ) {
        parent::__construct($httpClient, $logger);
    }

    /**
     * Fetches current time and timezone for the given IP
     *
     * @throws TimeResolutionException If API key is missing or request fails
     */
    public function getTime(IpAddressVo $ip): ServerTimeDto
    {
        if (empty($this->apiKey)) {
            throw new TimeResolutionException(self::ERROR_MISSING_API_KEY);
        }

        try {
            $data = $this->httpClient->getJson(
                sprintf(ApiEndpoint::IpGeolocation->value, $this->apiKey, $ip->value)
            );

            $timezone = $data['timezone'] ?? null;
            $datetime = $data['date_time'] ?? null;

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
