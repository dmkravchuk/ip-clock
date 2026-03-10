# Changelog

## [1.0.0] - 2026-03-10

### Added
- PSR-20 compliant `Clock` implementing `\Psr\Clock\ClockInterface`
- Auto-detection of server's external IP via ipify.org
- Three time providers with fallback chain:
    - Primary: timeapi.io
    - Fallback #1: worldtimeapi.org
    - Fallback #2: ipgeolocation.io
- Graceful degradation to UTC if all providers fail
- PSR-3 logger support
- Unit, Feature and Integration tests
