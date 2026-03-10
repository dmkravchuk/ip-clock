# dmkravchuk/ip-clock

A PSR-20 compliant Composer package that returns the correct server time and timezone based on the server's external IP address.

## Requirements

- PHP ^8.2
- Composer

## Installation

### Via Packagist
```bash
composer require dmkravchuk/ip-clock
```

### Via VCS (without Packagist)

Add to your `composer.json`:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/dmkravchuk/ip-clock"
    }
]
```

Then run:
```bash
composer require dmkravchuk/ip-clock
```

## Configuration

One of the time providers — [ipgeolocation.io](https://ipgeolocation.io) — requires a free API key.

1. Create a free account at https://ipgeolocation.io
2. Check the Dashboard the API KEY section
3. Copy your API key
4. Set it as an environment variable in `.env` file:
```
IPGEOLOCATION_API_KEY=your_api_key_here
```

> The package works without this key — ipgeolocation.io will simply be skipped if the key is not set.

## Usage

### Basic usage (auto-detect server IP)
```php
use DmKravchuk\IpClock\ClockFactory;

$clock = ClockFactory::create();
$now = $clock->now();

echo $now->format('Y-m-d H:i:s'); // 2026-03-10 14:00:00
echo $now->getTimezone()->getName(); // Europe/Kyiv
```

### With explicit IP address
```php
$clock = ClockFactory::create(ip: '8.8.8.8');
$now = $clock->now();

echo $now->getTimezone()->getName(); // America/Los_Angeles
```

### With custom PSR-3 logger
```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('ip-clock');
$logger->pushHandler(new StreamHandler('php://stdout'));

$clock = ClockFactory::create(logger: $logger);
```

### PSR-20 compatibility
```php
use Psr\Clock\ClockInterface;

function doSomething(ClockInterface $clock): void
{
    $now = $clock->now();
}

doSomething(ClockFactory::create());
```

## How it works

1. Resolves the server's external IP via [ipify.org](https://ipify.org)
2. Determines the timezone using a chain of time providers:
    - **Primary:** [timeapi.io](https://timeapi.io) — free, no API key required
    - **Fallback #1:** [worldtimeapi.org](https://worldtimeapi.org) — free, no API key required
    - **Fallback #2:** [ipgeolocation.io](https://ipgeolocation.io) — free API key required
3. If all providers fail — returns current time in **UTC**

## Running tests
```bash
composer test
```

## License

MIT
