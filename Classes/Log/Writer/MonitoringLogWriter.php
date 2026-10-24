<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Log\Writer;

use Tenryuubito\WizMonitorClient\Error\ErrorRecorder;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class MonitoringLogWriter extends AbstractWriter
{
    private const OWN_COMPONENT_PREFIX = 'Tenryuubito.WizMonitorClient';

    private static bool $isWriting = false;

    public function writeLog(LogRecord $record): WriterInterface
    {
        if (self::$isWriting || str_starts_with((string)$record->getComponent(), self::OWN_COMPONENT_PREFIX)) {
            return $this;
        }
        self::$isWriting = true;
        try {
            GeneralUtility::makeInstance(ErrorRecorder::class)->recordLogRecord($record);
        } catch (\Throwable) {
        } finally {
            self::$isWriting = false;
        }
        return $this;
    }
}
