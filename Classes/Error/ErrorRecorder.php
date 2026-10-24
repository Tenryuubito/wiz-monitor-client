<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Error;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tenryuubito\WizMonitorClient\Domain\Repository\ErrorRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Log\LogRecord;

#[Autoconfigure(public: true)]
final readonly class ErrorRecorder
{
    public function __construct(
        private ErrorOccurrenceFactory $errorOccurrenceFactory,
        private ErrorFingerprint $errorFingerprint,
        private ErrorRepository $errorRepository,
        private Context $context,
    ) {}

    public function recordLogRecord(LogRecord $record): void
    {
        $occurrence = $this->errorOccurrenceFactory->createFromLogRecord($record);
        $fingerprint = $this->errorFingerprint->create($occurrence);
        $timestamp = (int)$this->context->getPropertyFromAspect('date', 'timestamp');

        if ($this->errorRepository->incrementOccurrences($fingerprint, $occurrence->level, $timestamp)) {
            return;
        }
        try {
            $this->errorRepository->add($fingerprint, $occurrence, $timestamp);
        } catch (UniqueConstraintViolationException) {
            $this->errorRepository->incrementOccurrences($fingerprint, $occurrence->level, $timestamp);
        }
    }
}
