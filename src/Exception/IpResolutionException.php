<?php

namespace DmKravchuk\IpClock\Exception;

use RuntimeException;

/**
 * Thrown if the server's external IP address cannot be resolved
 */
class IpResolutionException extends RuntimeException {}
