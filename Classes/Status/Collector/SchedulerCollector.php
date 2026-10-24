<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final readonly class SchedulerCollector implements StatusCollectorInterface
{
    private const TABLE_NAME = 'tx_scheduler_task';
    private const MAXIMUM_DESCRIPTION_LENGTH = 255;

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getIdentifier(): string
    {
        return 'scheduler';
    }

    public function collect(StatusRequest $request): array
    {
        if (!ExtensionManagementUtility::isLoaded('scheduler')) {
            return ['installed' => false, 'tasks' => []];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'tasktype', 'description', 'disable', 'nextexecution', 'lastexecution_time', 'lastexecution_failure')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        return [
            'installed' => true,
            'tasks' => array_map(static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'type' => (string)$row['tasktype'],
                'description' => mb_substr((string)$row['description'], 0, self::MAXIMUM_DESCRIPTION_LENGTH),
                'disabled' => (bool)$row['disable'],
                'nextExecution' => (int)$row['nextexecution'],
                'lastExecution' => (int)$row['lastexecution_time'],
                'failed' => (string)$row['lastexecution_failure'] !== '',
            ], $rows),
        ];
    }
}
