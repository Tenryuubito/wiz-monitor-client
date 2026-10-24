<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status;

final readonly class StatusRequest
{
    public const MAXIMUM_PAGE_UIDS = 50;
    private const MAXIMUM_JSON_DEPTH = 8;

    public function __construct(
        public int $errorCursor,
        public array $pageUids = [],
    ) {}

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, self::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $data = [];
        }
        $data = is_array($data) ? $data : [];
        $errorCursor = is_int($data['errorCursor'] ?? null) ? $data['errorCursor'] : 0;
        $pageUids = is_array($data['pageUids'] ?? null) ? self::normalizePageUids($data['pageUids']) : [];
        return new self(max(0, $errorCursor), $pageUids);
    }

    public static function normalizePageUids(array $pageUids): array
    {
        return array_slice(array_values(array_unique(array_filter(
            $pageUids,
            static fn(mixed $pageUid): bool => is_int($pageUid) && $pageUid > 0,
        ))), 0, self::MAXIMUM_PAGE_UIDS);
    }

    public function toJson(): string
    {
        return json_encode(['errorCursor' => $this->errorCursor, 'pageUids' => $this->pageUids], JSON_THROW_ON_ERROR);
    }
}
