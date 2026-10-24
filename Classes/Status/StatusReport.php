<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Tenryuubito\WizMonitorClient\Error\MessageSanitizer;
use TYPO3\CMS\Core\Context\Context;

final readonly class StatusReport
{
    public const SCHEMA_VERSION = 1;
    public const STATE_COLLECTED = 'collected';
    public const STATE_FAILED = 'failed';

    public function __construct(
        #[AutowireIterator(StatusCollectorInterface::TAG_NAME)]
        private iterable $collectors,
        private MessageSanitizer $messageSanitizer,
        private Context $context,
    ) {}

    public function build(StatusRequest $request): array
    {
        $sections = [];
        foreach ($this->collectors as $collector) {
            $sections[$collector->getIdentifier()] = $this->collectSection($collector, $request);
        }
        return [
            'schema' => self::SCHEMA_VERSION,
            'generatedAt' => (int)$this->context->getPropertyFromAspect('date', 'timestamp'),
            'collectors' => $sections,
        ];
    }

    private function collectSection(StatusCollectorInterface $collector, StatusRequest $request): array
    {
        try {
            return ['state' => self::STATE_COLLECTED, 'data' => $collector->collect($request)];
        } catch (\Throwable $throwable) {
            return ['state' => self::STATE_FAILED, 'message' => $this->messageSanitizer->sanitizeMessage($throwable->getMessage())];
        }
    }
}
