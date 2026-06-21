<?php

namespace Tests\Unit;

use App\Services\MailConfigService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MailConfigServiceTest extends TestCase
{
    #[DataProvider('smtpHostNormalizationProvider')]
    public function test_normalize_smtp_host(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, MailConfigService::normalizeSmtpHost($input));
    }

    public static function smtpHostNormalizationProvider(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'valid hostname' => ['mail.example.com', 'mail.example.com'],
            'email address' => ['quiz@manuelcode.info', 'mail.manuelcode.info'],
            'ssl scheme prefix' => ['ssl://mail.example.com', 'mail.example.com'],
            'host with port' => ['mail.example.com:465', 'mail.example.com'],
            'email with ssl prefix' => ['ssl://quiz@manuelcode.info:465', 'mail.manuelcode.info'],
        ];
    }
}
