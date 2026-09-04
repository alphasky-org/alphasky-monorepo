<?php

namespace Alphasky\PluginManagement\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class AlphaskyMonorepoUpdater
{
    public function status(): array
    {
        $localVersion = $this->localVersion();
        $remoteVersion = $this->remoteVersion();

        return [
            'local_version' => $localVersion,
            'remote_version' => $remoteVersion,
            'update_available' => $remoteVersion !== '' && ($localVersion === '' || version_compare($remoteVersion, $localVersion, '>')),
            'can_update' => is_dir($this->localPath() . '/.git'),
        ];
    }

    public function checkAndUpdate(): array
    {
        $localPath = $this->localPath();
        $localVersion = $this->localVersion();
        $remoteVersion = $this->remoteVersion();

        if ($remoteVersion === '') {
            return [
                'updated' => false,
                'reason' => 'remote_version_unavailable',
                'local_version' => $localVersion,
            ];
        }

        if ($localVersion !== '' && ! version_compare($remoteVersion, $localVersion, '>')) {
            return [
                'updated' => false,
                'reason' => 'already_latest',
                'local_version' => $localVersion,
                'remote_version' => $remoteVersion,
            ];
        }

        if (! is_dir($localPath . '/.git')) {
            return [
                'updated' => false,
                'reason' => 'not_git_repository',
                'local_path' => $localPath,
                'local_version' => $localVersion,
                'remote_version' => $remoteVersion,
            ];
        }

        $this->runGit(['remote', 'set-url', 'origin', $this->repositoryUrl()], $localPath);
        $this->runGit(['fetch', '--depth=1', 'origin', $this->branch()], $localPath);
        $this->runGit(['reset', '--hard', 'FETCH_HEAD'], $localPath);

        if (config('packages.plugin-management.general.alphasky_monorepo.clean_untracked', true)) {
            $this->runGit(['clean', '-fd'], $localPath);
        }

        return [
            'updated' => true,
            'local_version' => $localVersion,
            'remote_version' => $remoteVersion,
            'current_version' => $this->localVersion(),
        ];
    }

    public function shouldUpdate(): bool
    {
        $localVersion = $this->localVersion();
        $remoteVersion = $this->remoteVersion();

        return $remoteVersion !== '' && ($localVersion === '' || version_compare($remoteVersion, $localVersion, '>'));
    }

    public function localVersion(): string
    {
        $versionFile = $this->localPath() . '/VERSION';

        return is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : '';
    }

    public function remoteVersion(): string
    {
        try {
            $response = Http::timeout(15)->get($this->remoteVersionUrl());

            if (! $response->successful()) {
                return '';
            }

            return trim($response->body());
        } catch (Throwable $exception) {
            Log::warning('Unable to check Alphasky monorepo version.', [
                'message' => $exception->getMessage(),
            ]);

            return '';
        }
    }

    protected function runGit(array $arguments, string $workingDirectory): void
    {
        $process = new Process(array_merge(['git', '-C', $workingDirectory], $arguments));
        $process->setTimeout((int) config('packages.plugin-management.general.alphasky_monorepo.update_timeout', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    protected function localPath(): string
    {
        return base_path(trim((string) config('packages.plugin-management.general.alphasky_monorepo.path', 'vendor/alphasky'), '/'));
    }

    protected function repositoryUrl(): string
    {
        return (string) config('packages.plugin-management.general.alphasky_monorepo.repository', 'https://github.com/alphasky-org/alphasky-monorepo.git');
    }

    protected function branch(): string
    {
        return (string) config('packages.plugin-management.general.alphasky_monorepo.branch', 'main');
    }

    protected function remoteVersionUrl(): string
    {
        return (string) config('packages.plugin-management.general.alphasky_monorepo.version_url', 'https://raw.githubusercontent.com/alphasky-org/alphasky-monorepo/main/VERSION');
    }
}
