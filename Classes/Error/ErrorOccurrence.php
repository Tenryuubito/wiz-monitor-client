<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Error;

final readonly class ErrorOccurrence
{
    public function __construct(
        public string $level,
        public string $channel,
        public string $message,
        public string $exceptionClass,
        public string $exceptionCode,
        public string $file,
        public int $line,
        public string $requestUrl,
    ) {}
}
