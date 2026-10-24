<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Security;

final readonly class RequestSigner
{
    public const HEADER_TIMESTAMP = 'X-Wiz-Monitor-Timestamp';
    public const HEADER_NONCE = 'X-Wiz-Monitor-Nonce';
    public const HEADER_SIGNATURE = 'X-Wiz-Monitor-Signature';
    public const MAXIMUM_CLOCK_SKEW_SECONDS = 60;
    private const ALGORITHM = 'sha256';
    private const NONCE_PATTERN = '/^[a-f0-9]{32}$/';
    private const NONCE_BYTES = 16;

    public function createNonce(): string
    {
        return bin2hex(random_bytes(self::NONCE_BYTES));
    }

    public function signRequest(string $secret, string $method, string $path, int $timestamp, string $nonce, string $body): string
    {
        return $this->sign($secret, ['request', strtoupper($method), $path, (string)$timestamp, $nonce, hash(self::ALGORITHM, $body)]);
    }

    public function signResponse(string $secret, string $requestNonce, int $timestamp, string $body): string
    {
        return $this->sign($secret, ['response', $requestNonce, (string)$timestamp, hash(self::ALGORITHM, $body)]);
    }

    public function matches(string $expectedSignature, string $givenSignature): bool
    {
        return hash_equals($expectedSignature, $givenSignature);
    }

    public function isTimestampWithinTolerance(int $timestamp, int $now): bool
    {
        return abs($now - $timestamp) <= self::MAXIMUM_CLOCK_SKEW_SECONDS;
    }

    public function isValidNonce(string $nonce): bool
    {
        return preg_match(self::NONCE_PATTERN, $nonce) === 1;
    }

    private function sign(string $secret, array $parts): string
    {
        return hash_hmac(self::ALGORITHM, implode("\n", $parts), $secret);
    }
}
