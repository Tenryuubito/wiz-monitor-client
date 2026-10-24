<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Install\Service\EnableFileService;

final readonly class HardeningCollector implements StatusCollectorInterface
{
    private const FAILED_LOGIN_WINDOW_SECONDS = 86400;
    private const UNRESTRICTED_TRUSTED_HOSTS_PATTERN = '.*';

    public function __construct(
        private ConnectionPool $connectionPool,
        private Context $context,
    ) {}

    public function getIdentifier(): string
    {
        return 'hardening';
    }

    public function collect(StatusRequest $request): array
    {
        $systemConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SYS'] ?? [];
        $trustedHostsPattern = (string)($systemConfiguration['trustedHostsPattern'] ?? '');

        return [
            'installToolEnableFileExists' => class_exists(EnableFileService::class) && EnableFileService::installToolEnableFileExists(),
            'displayErrors' => (int)($systemConfiguration['displayErrors'] ?? -1),
            'trustedHostsPatternRestricted' => $trustedHostsPattern !== '' && $trustedHostsPattern !== self::UNRESTRICTED_TRUSTED_HOSTS_PATTERN,
            'activeAdministratorCount' => $this->countActiveAdministrators(),
            'failedLoginsLastDay' => $this->countFailedLoginsSince(
                (int)$this->context->getPropertyFromAspect('date', 'timestamp') - self::FAILED_LOGIN_WINDOW_SECONDS
            ),
        ];
    }

    private function countActiveAdministrators(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        return (int)$queryBuilder
            ->count('uid')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }

    private function countFailedLoginsSince(int $timestamp): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_log');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(SystemLogType::LOGIN, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('action', $queryBuilder->createNamedParameter(SystemLogLoginAction::ATTEMPT, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('error', $queryBuilder->createNamedParameter(SystemLogErrorClassification::SECURITY_NOTICE, Connection::PARAM_INT)),
                $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($timestamp, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
