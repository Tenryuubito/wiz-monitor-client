<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

final readonly class PageUrlCollector implements StatusCollectorInterface
{
    private const TABLE_NAME = 'pages';

    public function __construct(
        private ConnectionPool $connectionPool,
        private SiteFinder $siteFinder,
        private Context $context,
    ) {}

    public function getIdentifier(): string
    {
        return 'pages';
    }

    public function collect(StatusRequest $request): array
    {
        $items = [];
        $unresolved = [];
        foreach ($request->pageUids as $pageUid) {
            $pageItems = $this->resolvePageUrls($pageUid);
            if ($pageItems === []) {
                $unresolved[] = $pageUid;
            }
            array_push($items, ...$pageItems);
        }
        return [
            'items' => $items,
            'unresolved' => $unresolved,
        ];
    }

    private function resolvePageUrls(int $pageUid): array
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException) {
            return [];
        }
        if (!$this->isPageAvailable($pageUid, $site->getDefaultLanguage())) {
            return [];
        }
        $items = [];
        foreach ($site->getLanguages() as $language) {
            if ($language->getLanguageId() !== 0 && !$this->isPageAvailable($pageUid, $language)) {
                continue;
            }
            $items[] = [
                'pageUid' => $pageUid,
                'languageId' => $language->getLanguageId(),
                'url' => $this->generateUrl($site, $pageUid, $language),
            ];
        }
        return $items;
    }

    private function isPageAvailable(int $pageUid, SiteLanguage $language): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer($this->context));
        $pageCondition = $language->getLanguageId() === 0
            ? $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT))
            : $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT));
        $count = $queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $pageCondition,
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language->getLanguageId(), Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('doktype', $queryBuilder->createNamedParameter(PageRepository::DOKTYPE_DEFAULT, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
        return (int)$count > 0;
    }

    private function generateUrl(Site $site, int $pageUid, SiteLanguage $language): string
    {
        return (string)$site->getRouter()->generateUri($pageUid, ['_language' => $language]);
    }
}
