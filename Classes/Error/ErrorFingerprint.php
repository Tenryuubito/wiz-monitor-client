<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Error;

final readonly class ErrorFingerprint
{
    public function __construct(
        private MessageSanitizer $messageSanitizer,
    ) {}

    public function create(ErrorOccurrence $occurrence): string
    {
        return hash('sha256', implode("\n", [
            $occurrence->channel,
            $occurrence->exceptionClass,
            $occurrence->exceptionCode,
            $occurrence->file,
            (string)$occurrence->line,
            $this->messageSanitizer->normalizeForFingerprint($occurrence->message),
        ]));
    }
}
