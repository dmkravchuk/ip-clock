<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service\IpResolver;

use DmKravchuk\IpClock\Contract\IpResolverInterface;
use DmKravchuk\IpClock\Data\IpAddressVo;
use DmKravchuk\IpClock\Enum\ApiEndpoint;
use DmKravchuk\IpClock\Exception\IpResolutionException;
use DmKravchuk\IpClock\Service\HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Resolves the server's external IP via ipify.org
 * If an IP is explicitly provided, validates and returns it directly
 */
final class ExternalIpResolver implements IpResolverInterface
{
    private const ERROR_MISSING_IP_FIELD = 'IP detection response is missing the "ip" field';
    private const ERROR_INVALID_DETECTED_IP = 'Detected IP address is not valid: %s';
    private const ERROR_INVALID_PROVIDED_IP = 'Provided IP address is invalid: %s';
    private const ERROR_PARSE_FAILED = 'Failed to parse IP detection response: %s';
    private const ERROR_SERVICE_UNAVAILABLE = 'IP detection service is unavailable: %s';
    private const LOG_PROVIDED_IP = 'Using provided IP address';
    private const LOG_DETECTED_IP = 'Detected external IP address';

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Validates and returns the provided IP, or auto-detects the server's external IP
     */
    public function resolve(?string $ip = null): IpAddressVo
    {
        if ($ip !== null) {
            return $this->validateProvidedIp($ip);
        }

        return $this->detectExternalIp();
    }

    /**
     * Validates an explicitly provided IP address
     */
    private function validateProvidedIp(string $ip): IpAddressVo
    {
        try {
            $vo = new IpAddressVo($ip);
            $this->logger->info(self::LOG_PROVIDED_IP, ['ip' => $ip]);

            return $vo;
        } catch (InvalidArgumentException $e) {
            throw new IpResolutionException(
                message: sprintf(self::ERROR_INVALID_PROVIDED_IP, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * Hits ipify.org to figure out the server's real external IP
     */
    private function detectExternalIp(): IpAddressVo
    {
        try {
            $data = $this->httpClient->getJson(ApiEndpoint::IpDetect->value);

            $ip = $data['ip'] ?? null;
            if (empty($ip)) {
                throw new IpResolutionException(self::ERROR_MISSING_IP_FIELD);
            }

            $vo = new IpAddressVo($ip);
            $this->logger->info(self::LOG_DETECTED_IP, ['ip' => $ip]);

            return $vo;
        } catch (InvalidArgumentException $e) {
            throw new IpResolutionException(
                message: sprintf(self::ERROR_INVALID_DETECTED_IP, $e->getMessage()),
                previous: $e,
            );
        } catch (\JsonException $e) {
            throw new IpResolutionException(
                message: sprintf(self::ERROR_PARSE_FAILED, $e->getMessage()),
                previous: $e,
            );
        } catch (GuzzleException $e) {
            throw new IpResolutionException(
                message: sprintf(self::ERROR_SERVICE_UNAVAILABLE, $e->getMessage()),
                previous: $e,
            );
        }
    }
}
