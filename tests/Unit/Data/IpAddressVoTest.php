<?php

declare(strict_types=1);

namespace DmKravchuk\IpClock\Tests\Unit\Data;

use DmKravchuk\IpClock\Data\IpAddressVo;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Test,
};
use PHPUnit\Framework\TestCase;

class IpAddressVoTest extends TestCase
{
    #[Test]
    #[DataProvider('validIpProvider')]
    public function it_accepts_valid_ip_addresses(string $ip): void
    {
        $vo = new IpAddressVo($ip);

        $this->assertSame($ip, $vo->value);
        $this->assertSame($ip, (string) $vo);
    }

    #[Test]
    #[DataProvider('invalidIpProvider')]
    public function it_rejects_invalid_ip_addresses(string $ip): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IpAddressVo($ip);
    }

    public static function validIpProvider(): array
    {
        return [
            'IPv4 public'     => ['8.8.8.8'],
            'IPv4 loopback'   => ['127.0.0.1'],
            'IPv6 compressed' => ['::1'],
        ];
    }

    public static function invalidIpProvider(): array
    {
        return [
            'hostname'     => ['example.com'],
            'empty string' => [''],
            'partial IP'   => ['192.168'],
            'with port'    => ['192.168.1.1:80'],
        ];
    }
}
