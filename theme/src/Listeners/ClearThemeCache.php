<?php

namespace Alphasky\Theme\Listeners;

use Alphasky\Base\Events\CacheCleared;
use Alphasky\Theme\Facades\Manager as ThemeManager;

class ClearThemeCache
{
    public function handle(CacheCleared $event): void
    {
        if (in_array($event->cacheType, ['framework', 'all'])) {
            ThemeManager::clearCache();
        }
    }
}
