<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Error;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Log\LogRecord;

final readonly class ErrorOccurrenceFactory
{
    private const MAXIMUM_CHANNEL_LENGTH = 255;

    public function __construct(
        private MessageSanitizer $messageSanitizer,
    ) {}

    public function createFromLogRecord(LogRecord $record): ErrorOccurrence
    {
        $data = $record->getData();
        $exception = ($data['exception'] ?? null) instanceof \Throwable ? $data['exception'] : null;
        $requestUrl = $this->toString($data['request_url'] ?? null);

        return new ErrorOccurrence(
            level: $record->getLevel(),
            channel: mb_substr((string)$record->getComponent(), 0, self::MAXIMUM_CHANNEL_LENGTH),
            message: $this->messageSanitizer->sanitizeMessage($this->resolveMessage($record, $data, $exception)),
            exceptionClass: $this->toString($data['exception_class'] ?? null) ?: ($exception !== null ? $exception::class : ''),
            exceptionCode: $this->toString($data['exception_code'] ?? $data['code'] ?? null) ?: ($exception !== null ? (string)$exception->getCode() : ''),
            file: $this->toRelativePath($this->toString($data['file'] ?? null) ?: ($exception?->getFile() ?? '')),
            line: (int)($data['line'] ?? $exception?->getLine() ?? 0),
            requestUrl: $this->messageSanitizer->sanitizeUrl($requestUrl !== '' ? $requestUrl : $this->resolveCurrentRequestUrl()),
        );
    }

    private function resolveMessage(LogRecord $record, array $data, ?\Throwable $exception): string
    {
        $message = $this->toString($data['message'] ?? null);
        if ($message !== '') {
            return $message;
        }
        if ($exception !== null) {
            return $exception->getMessage();
        }
        $replacements = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value instanceof \Stringable) {
                $replacements['{' . $key . '}'] = (string)$value;
            }
        }
        return strtr((string)$record->getMessage(), $replacements);
    }

    private function resolveCurrentRequestUrl(): string
    {
        if (Environment::isCli()) {
            return '';
        }
        return NormalizedParams::createFromServerParams($_SERVER)->getRequestUrl();
    }

    private function toRelativePath(string $file): string
    {
        $projectPath = Environment::getProjectPath() . '/';
        return str_starts_with($file, $projectPath) ? substr($file, strlen($projectPath)) : $file;
    }

    private function toString(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string)$value : '';
    }
}
