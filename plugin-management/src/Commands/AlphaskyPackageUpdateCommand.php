<?php
namespace Alphasky\PluginManagement\Commands;

use Alphasky\PluginManagement\Services\AlphaskyMonorepoUpdater;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class AlphaskyPackageUpdateCommand extends Command
{
    protected $signature = 'cms:alphasky:update {--managed : Require managed website opt-in}';
    protected $description = 'Safely update the shared Alphasky package and refresh published assets.';

    public function handle(AlphaskyMonorepoUpdater $updater): int
    {
        if ($this->option('managed') && ! config('packages.plugin-management.general.alphasky_monorepo.managed_updates', false)) {
            $this->line('Managed updates are not enabled.');
            return self::SUCCESS;
        }
        try {
            $result = $updater->checkAndUpdate();
            if (($result['reason'] ?? '') === 'remote_version_unavailable') throw new \RuntimeException('Unable to check the release version.');
            // Fresh processes load the newly installed package. Repeat on the next run if finalization failed.
            foreach ([['cms:publish:assets'], ['config:clear'], ['view:clear']] as $command) {
                $process = new Process(array_merge([PHP_BINARY, base_path('artisan')], $command), base_path());
                $process->setTimeout(180);
                $process->mustRun();
            }
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
