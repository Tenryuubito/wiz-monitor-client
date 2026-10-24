<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Status\Collector;

use Composer\InstalledVersions;
use Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;

final readonly class PackageCollector implements StatusCollectorInterface
{
    public function getIdentifier(): string
    {
        return 'packages';
    }

    public function collect(StatusRequest $request): array
    {
        $packages = [];
        foreach (InstalledVersions::getAllRawData() as $dataset) {
            foreach ($dataset['versions'] ?? [] as $name => $package) {
                if (!isset($package['pretty_version']) || isset($packages[$name])) {
                    continue;
                }
                $packages[$name] = [
                    'name' => $name,
                    'version' => $package['pretty_version'],
                    'type' => $package['type'] ?? '',
                    'devRequirement' => (bool)($package['dev_requirement'] ?? false),
                ];
            }
        }
        ksort($packages);

        return [
            'rootPackage' => InstalledVersions::getRootPackage()['name'],
            'packages' => array_values($packages),
        ];
    }
}
