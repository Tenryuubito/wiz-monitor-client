<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Error;

final readonly class MessageSanitizer
{
    public const REDACTION = '[redacted]';
    private const MAXIMUM_MESSAGE_LENGTH = 2000;
    private const MAXIMUM_URL_LENGTH = 1024;
    private const SENSITIVE_PATTERNS = [
        '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
        '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
        '/\b[a-f0-9]{32,}\b/i',
        '/\b[A-Za-z0-9_\-]{40,}\b/',
    ];

    public function sanitizeMessage(string $message): string
    {
        $withoutQueryValues = preg_replace('/([?&][^=\s&]+=)[^&\s"\']*/', '$1' . self::REDACTION, $message) ?? '';
        return mb_substr($this->redactSensitiveValues($withoutQueryValues), 0, self::MAXIMUM_MESSAGE_LENGTH);
    }

    public function sanitizeUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }
        $parts = parse_url($url);
        if ($parts === false) {
            return '';
        }
        $origin = isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '')
            : '';
        return mb_substr($origin . $this->redactSensitiveValues($parts['path'] ?? ''), 0, self::MAXIMUM_URL_LENGTH);
    }

    public function normalizeForFingerprint(string $message): string
    {
        return preg_replace(['/\d+/', '/\s+/'], ['#', ' '], $message) ?? $message;
    }

    private function redactSensitiveValues(string $value): string
    {
        return preg_replace(self::SENSITIVE_PATTERNS, self::REDACTION, $value) ?? '';
    }
}
