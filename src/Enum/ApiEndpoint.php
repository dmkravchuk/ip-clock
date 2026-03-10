<?php

namespace DmKravchuk\IpClock\Enum;

/**
 * External API endpoints used for IP detection and time resolution
 */
enum ApiEndpoint: string
{
    case IpDetect = 'https://api.ipify.org?format=json';
    case WorldTimeApi = 'https://worldtimeapi.org/api/ip/%s';
    case TimeApi = 'https://timeapi.io/api/Time/current/ip?ipAddress=%s';
    case IpGeolocation = 'https://api.ipgeolocation.io/timezone?apiKey=%s&ip=%s';
}
