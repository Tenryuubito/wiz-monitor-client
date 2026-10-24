<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Security;

final readonly class SecretProvider
{
    public const ENVIRONMENT_VARIABLE = 'WIZ_MONITOR_CLIENT_SECRET';
    public const MINIMUM_SECRET_LENGTH = 32;

    public function getSecret(): ?string
    {
        $secret = getenv(self::ENVIRONMENT_VARIABLE);
        if (!is_string($secret) || $secret === '') {
            $secret = $_ENV[self::ENVIRONMENT_VARIABLE] ?? '';
        }
        return is_string($secret) && strlen($secret) >= self::MINIMUM_SECRET_LENGTH ? $secret : null;
    }
}
