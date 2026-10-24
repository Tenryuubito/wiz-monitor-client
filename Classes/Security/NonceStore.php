<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

final readonly class NonceStore
{
    public const CACHE_IDENTIFIER = 'wiz_monitor_client_nonce';
    private const LIFETIME_SECONDS = 300;

    public function __construct(
        #[Autowire(service: 'cache.wiz_monitor_client_nonce')]
        private FrontendInterface $cache,
    ) {}

    public function consume(string $nonce): bool
    {
        if ($this->cache->has($nonce)) {
            return false;
        }
        $this->cache->set($nonce, true, [], self::LIFETIME_SECONDS);
        return true;
    }
}
