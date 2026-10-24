<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status;

final readonly class StatusRequest
{
    private const MAXIMUM_JSON_DEPTH = 8;

    public function __construct(
        public int $errorCursor,
    ) {}

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, self::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $data = [];
        }
        $errorCursor = is_array($data) && is_int($data['errorCursor'] ?? null) ? $data['errorCursor'] : 0;
        return new self(max(0, $errorCursor));
    }

    public function toJson(): string
    {
        return json_encode(['errorCursor' => $this->errorCursor], JSON_THROW_ON_ERROR);
    }
}
