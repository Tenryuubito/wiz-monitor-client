<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Core\Environment;

final readonly class StorageCollector implements StatusCollectorInterface
{
    public function getIdentifier(): string
    {
        return 'storage';
    }

    public function collect(StatusRequest $request): array
    {
        $varPath = Environment::getVarPath();
        $varPathExists = is_dir($varPath);

        return [
            'varPathWritable' => $varPathExists && is_writable($varPath),
            'publicTemporaryPathWritable' => is_writable(Environment::getPublicPath() . '/typo3temp'),
            'freeBytes' => $varPathExists ? (int)disk_free_space($varPath) : 0,
            'totalBytes' => $varPathExists ? (int)disk_total_space($varPath) : 0,
        ];
    }
}
