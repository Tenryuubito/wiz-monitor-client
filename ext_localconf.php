<?php

declare(strict_types=1);

use Psr\Log\LogLevel;
use Tenryuubito\WizMonitorClient\Log\Writer\MonitoringLogWriter;
use Tenryuubito\WizMonitorClient\Security\NonceStore;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::ERROR][MonitoringLogWriter::class] = [];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][NonceStore::CACHE_IDENTIFIER] ??= [
    'frontend' => VariableFrontend::class,
    'backend' => Typo3DatabaseBackend::class,
    'groups' => ['system'],
];
