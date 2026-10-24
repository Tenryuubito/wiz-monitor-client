<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final readonly class EnvironmentCollector implements StatusCollectorInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private SiteFinder $siteFinder,
        private Typo3Version $typo3Version,
    ) {}

    public function getIdentifier(): string
    {
        return 'environment';
    }

    public function collect(StatusRequest $request): array
    {
        $connection = $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        return [
            'typo3Version' => $this->typo3Version->getVersion(),
            'phpVersion' => PHP_VERSION,
            'operatingSystem' => PHP_OS_FAMILY,
            'applicationContext' => (string)Environment::getContext(),
            'composerMode' => Environment::isComposerMode(),
            'databasePlatform' => (new \ReflectionClass($connection->getDatabasePlatform()))->getShortName(),
            'databaseServerVersion' => $connection->getServerVersion(),
            'memoryLimit' => (string)ini_get('memory_limit'),
            'maxExecutionTime' => (int)ini_get('max_execution_time'),
            'siteBaseUrls' => array_values(array_map(
                static fn(Site $site): string => (string)$site->getBase(),
                $this->siteFinder->getAllSites(),
            )),
        ];
    }
}
