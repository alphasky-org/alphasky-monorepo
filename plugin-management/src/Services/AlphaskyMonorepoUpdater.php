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

        $blockedReason = $this->blockedReason();

        return [
            'local_version' => $localVersion,
            'remote_version' => $remoteVersion,
            'update_available' => $remoteVersion !== '' && ($localVersion === '' || version_compare($remoteVersion, $localVersion, '>')),
            'can_update' => $blockedReason === null,
            'blocked_reason' => $blockedReason,
            'managed_updates' => (bool) config('packages.plugin-management.general.alphasky_monorepo.managed_updates', false),
        ];
    }

    public function checkAndUpdate(): array
    {
        $reason = $this->blockedReason();
        if ($reason !== null) {
            throw new \RuntimeException(trans('packages/plugin-management::plugin.alphasky_monorepo.' . $reason));
        }
        $lock = fopen($this->localPath() . '/.git/alphasky-update.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            throw new \RuntimeException('An Alphasky update is already running.');
        }
        try {
            return $this->updateCleanRepository();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function updateCleanRepository(): array
    {
        $localPath = $this->localPath();
        if ($this->hasLocalChanges()) {
            throw new \RuntimeException('Local Alphasky changes must be reviewed and merged before updating; automatic replacement was blocked.');
        }
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
        $this->runGit(['fetch', 'origin', $this->branch()], $localPath);
        if ($this->hasLocalChanges()) {
            throw new \RuntimeException('Local Alphasky changes appeared during the update; update stopped.');
        }
        $this->runGit(['update-ref', 'refs/alphasky/previous', 'HEAD'], $localPath);
        $this->runGit(['merge', '--ff-only', 'FETCH_HEAD'], $localPath);


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

    protected function blockedReason(): ?string
    {
        if (! is_dir($this->localPath() . '/.git')) return 'not_git_repository';
        if ($this->hasLocalChanges()) return 'local_changes';
        if (! is_writable($this->localPath()) || ! is_writable($this->localPath() . '/.git')) return 'not_writable';
        return null;
    }

    protected function hasLocalChanges(): bool
    {
        if (! is_dir($this->localPath() . '/.git')) {
            return true;
        }
        $process = new Process(['git', '-C', $this->localPath(), 'status', '--porcelain', '--untracked-files=all']);
        $process->setTimeout(30);
        $process->run();

        // Fail closed when repository status cannot be verified.
        return ! $process->isSuccessful() || trim($process->getOutput()) !== '';
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
