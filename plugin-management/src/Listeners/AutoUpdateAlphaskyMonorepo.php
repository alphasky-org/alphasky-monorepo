<?php

namespace Alphasky\PluginManagement\Listeners;

use Alphasky\Base\Events\SystemUpdatePublished;
use Alphasky\PluginManagement\Services\AlphaskyMonorepoUpdater;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoUpdateAlphaskyMonorepo
{
    public function __construct(protected AlphaskyMonorepoUpdater $updater)
    {
    }

    public function handle(SystemUpdatePublished $event): void
    {
        if (! config('packages.plugin-management.general.alphasky_monorepo.auto_update', true)) {
            return;
        }

        try {
            $result = $this->updater->checkAndUpdate();

            if ($result['updated'] ?? false) {
                Log::info('Alphasky monorepo updated.', $result);
            }
        } catch (Throwable $exception) {
            Log::warning('Unable to update Alphasky monorepo.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
