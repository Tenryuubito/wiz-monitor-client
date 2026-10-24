<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Tenryuubito\WizMonitorClient\Domain\Repository\ErrorRepository;
use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Context\Context;

final readonly class ErrorCollector implements StatusCollectorInterface
{
    private const PAGE_SIZE = 200;
    private const RETENTION_SECONDS = 30 * 86400;

    public function __construct(
        private ErrorRepository $errorRepository,
        private Context $context,
    ) {}

    public function getIdentifier(): string
    {
        return 'errors';
    }

    public function collect(StatusRequest $request): array
    {
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $this->errorRepository->removeLastSeenBefore($now - self::RETENTION_SECONDS);

        $rows = $this->errorRepository->findSeenSince($request->errorCursor, self::PAGE_SIZE + 1);
        $hasMore = count($rows) > self::PAGE_SIZE;
        $items = array_map($this->mapRow(...), array_slice($rows, 0, self::PAGE_SIZE));
        $cursor = $items === [] ? $request->errorCursor : max(array_column($items, 'lastSeen'));

        return [
            'items' => $items,
            'hasMore' => $hasMore,
            'cursor' => $cursor,
        ];
    }

    private function mapRow(array $row): array
    {
        return [
            'fingerprint' => (string)$row['fingerprint'],
            'level' => (string)$row['level'],
            'channel' => (string)$row['channel'],
            'message' => (string)$row['message'],
            'exceptionClass' => (string)$row['exception_class'],
            'exceptionCode' => (string)$row['exception_code'],
            'file' => (string)$row['file'],
            'line' => (int)$row['line'],
            'requestUrl' => (string)$row['request_url'],
            'occurrences' => (int)$row['occurrences'],
            'firstSeen' => (int)$row['first_seen'],
            'lastSeen' => (int)$row['last_seen'],
        ];
    }
}
