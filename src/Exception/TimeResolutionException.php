<?php

namespace DmKravchuk\IpClock\Exception;

use RuntimeException;

/**
 * Thrown if a time provider fails to resolve the server time
 */
class TimeResolutionException extends RuntimeException {}
