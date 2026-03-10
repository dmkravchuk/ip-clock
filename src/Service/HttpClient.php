<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Handles all outgoing HTTP requests with shared defaults
 */
class HttpClient
{
    private const TIMEOUT = 5;
    private const CONNECT_TIMEOUT = 3;
    private const USER_AGENT = 'dmkravchuk/ip-clock';

    public function __construct(private readonly ClientInterface $client) {}

    /**
     * Fires a GET request and returns the response as a decoded array
     *
     * @return array<mixed>
     * @throws \JsonException If the response is not valid JSON
     * @throws GuzzleException If the request fails or times out
     */
    public function getJson(string $url): array
    {
        $response = $this->client->request('GET', $url, [
            'timeout' => self::TIMEOUT,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'headers' => ['User-Agent' => self::USER_AGENT],
        ]);

        return json_decode(
            json: (string) $response->getBody(),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
