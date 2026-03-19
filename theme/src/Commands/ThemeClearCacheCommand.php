<?php

namespace Alphasky\Theme\Commands;

use Alphasky\Theme\Facades\Manager as ThemeManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:theme:clear-cache', 'Clear theme cache')]
class ThemeClearCacheCommand extends Command
{
    public function handle(): int
    {
        ThemeManager::clearCache();

        $this->components->info('Theme cache cleared successfully!');

        return self::SUCCESS;
    }
}
