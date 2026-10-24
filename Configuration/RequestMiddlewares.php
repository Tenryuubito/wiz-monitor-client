<?php

declare(strict_types=1);

use Tenryuubito\WizMonitorClient\Middleware\StatusEndpointMiddleware;

return [
    'frontend' => [
        'tenryuubito/wiz-monitor-client/status-endpoint' => [
            'target' => StatusEndpointMiddleware::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/eid',
                'typo3/cms-frontend/maintenance-mode',
            ],
        ],
    ],
];
