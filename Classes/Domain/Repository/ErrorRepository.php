<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Domain\Repository;

use Tenryuubito\WizMonitorClient\Error\ErrorOccurrence;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final readonly class ErrorRepository
{
    public const TABLE_NAME = 'tx_wizmonitorclient_error';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function incrementOccurrences(string $fingerprint, string $level, int $timestamp): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $affectedRows = $queryBuilder
            ->update(self::TABLE_NAME)
            ->set('occurrences', $queryBuilder->quoteIdentifier('occurrences') . ' + 1', false)
            ->set('last_seen', $timestamp, true, Connection::PARAM_INT)
            ->set('level', $level)
            ->where($queryBuilder->expr()->eq('fingerprint', $queryBuilder->createNamedParameter($fingerprint)))
            ->executeStatement();
        return $affectedRows > 0;
    }

    public function add(string $fingerprint, ErrorOccurrence $occurrence, int $timestamp): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE_NAME)->insert(self::TABLE_NAME, [
            'fingerprint' => $fingerprint,
            'level' => $occurrence->level,
            'channel' => $occurrence->channel,
            'message' => $occurrence->message,
            'exception_class' => $occurrence->exceptionClass,
            'exception_code' => $occurrence->exceptionCode,
            'file' => $occurrence->file,
            'line' => $occurrence->line,
            'request_url' => $occurrence->requestUrl,
            'occurrences' => 1,
            'first_seen' => $timestamp,
            'last_seen' => $timestamp,
        ]);
    }

    public function findSeenSince(int $timestamp, int $limit): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        return $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->gte('last_seen', $queryBuilder->createNamedParameter($timestamp, Connection::PARAM_INT)))
            ->orderBy('last_seen')
            ->addOrderBy('uid')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function removeLastSeenBefore(int $timestamp): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where($queryBuilder->expr()->lt('last_seen', $queryBuilder->createNamedParameter($timestamp, Connection::PARAM_INT)))
            ->executeStatement();
    }
}
