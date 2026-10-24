<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG_NAME)]
interface StatusCollectorInterface
{
    public const TAG_NAME = 'wiz_monitor_client.status_collector';

    public function getIdentifier(): string;

    public function collect(StatusRequest $request): array;
}
