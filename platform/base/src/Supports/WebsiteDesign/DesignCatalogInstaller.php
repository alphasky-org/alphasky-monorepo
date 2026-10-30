<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use RuntimeException;
use Symfony\Component\Process\Process;

/** Runs as the tenant. Receives only authenticated central release metadata. */
class DesignCatalogInstaller
{
    public function install(array $packages, callable $download): void
    {
        if (!$packages || count($packages) > 24) throw new RuntimeException('invalid_catalog_packages');
        $root = storage_path('app/private/design-catalog');
        if (is_link($root)) throw new RuntimeException('unsafe_catalog_path');
        if (!is_dir($root) && !mkdir($root, 0700, true)) throw new RuntimeException('catalog_storage_unavailable');
        $lock = fopen($root.'/install.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('catalog_install_locked');
        try {
            foreach ($packages as $package) $this->one($package, $download, $root);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    protected function command(array $arguments): array
    {
        $p = new Process([PHP_BINARY, base_path('artisan'), ...$arguments], base_path(), null, null, 300);
        $p->run();
        if (!$p->isSuccessful()) throw new RuntimeException('catalog_activation_failed');
        return json_decode(trim($p->getOutput()), true) ?: [];
    }

    protected function active(): array
    {
        // Fresh process: activation changes providers and settings after this worker booted.
        $code = 'echo json_encode(array_values(get_active_plugins()));';
        $p = new Process([PHP_BINARY, base_path('artisan'), 'tinker', '--execute='.$code], base_path(), null, null, 60);
        $p->run(); $active = json_decode(trim($p->getOutput()), true);
        if (!$p->isSuccessful() || !is_array($active)) throw new RuntimeException('catalog_verification_failed');
        return $active;
    }

    private function one(array $p, callable $download, string $root): void
    {
        $name = $p['name'] ?? '';
        if (!is_string($name) || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $name)
            || !preg_match('/^[a-f0-9]{64}$/D', $p['sha256'] ?? '')
            || !preg_match('#^/api/v1/website-design/dependency/[a-f0-9-]{36}/'.preg_quote($name, '#').'$#D', $p['path'] ?? '')
            || !is_array($p['requires'] ?? null)) throw new RuntimeException('invalid_catalog_release');
        if (version_compare(get_core_version(), $p['minimum_core_version'] ?? '0.0.0', '<')) throw new RuntimeException('catalog_core_version');
        foreach ([base_path('platform'), base_path('platform/plugins')] as $dir) {
            if (is_link($dir)) throw new RuntimeException('unsafe_catalog_path');
            if (!is_dir($dir) && !mkdir($dir, 0755)) throw new RuntimeException('catalog_directory_unavailable');
        }
        $target = base_path('platform/plugins/'.$name);
        if (is_link($target)) throw new RuntimeException('unsafe_catalog_path');
        $active = $this->active();
        foreach ($p['requires'] as $id) {
            if (!is_string($id) || !str_starts_with($id, 'alphasky/') || !in_array(substr($id, 9), $active, true)) throw new RuntimeException('catalog_dependency_inactive');
        }
        $receipt = $root.'/'.$name.'.json';
        $saved = is_file($receipt) ? json_decode(file_get_contents($receipt), true) : [];
        if (($saved['status'] ?? '') === 'applying') throw new RuntimeException('catalog_install_requires_reconciliation');
        if (is_dir($target)) {
            $manifest = json_decode((string) @file_get_contents($target.'/plugin.json'), true);
            if (($manifest['id'] ?? '') !== 'alphasky/'.$name) throw new RuntimeException('catalog_existing_plugin_conflict');
            if (version_compare($manifest['version'] ?? '0', $p['version'] ?? '0', '<')) throw new RuntimeException('catalog_existing_plugin_needs_update');
            if (in_array($name, $active, true)) return; // Preserve installed/customized plugins; never overwrite them.
        } else {
            $bytes = $download($p['path']);
            if (!is_string($bytes) || strlen($bytes) > 50000000 || !hash_equals($p['sha256'], hash('sha256', $bytes))) throw new RuntimeException('catalog_hash_mismatch');
            $zipPath = $root.'/'.$p['sha256'].'.zip'; file_put_contents($zipPath, $bytes); chmod($zipPath, 0600);
            $zip = new \ZipArchive(); if ($zip->open($zipPath) !== true) throw new RuntimeException('invalid_catalog_archive');
            try {
                $total = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $e = $zip->statIndex($i); $entry = str_replace('\\', '/', $e['name']);
                    $zip->getExternalAttributesIndex($i, $os, $attr); $total += $e['size'];
                    if ($total > 200000000 || $zip->numFiles > 12000 || str_starts_with($entry, '/')
                        || preg_match('#(^|/)\.\.?(/|$)|^[a-z]:|\x00#i', $entry) || (($attr >> 16) & 0170000) === 0120000) throw new RuntimeException('unsafe_catalog_archive');
                }
                $manifest = json_decode((string) $zip->getFromName('plugin.json'), true);
                if (($manifest['id'] ?? '') !== 'alphasky/'.$name || ($manifest['version'] ?? '') !== ($p['version'] ?? '')
                    || ($manifest['require'] ?? []) !== $p['requires']) throw new RuntimeException('catalog_manifest_mismatch');
                $stage = $root.'/stage-'.bin2hex(random_bytes(8)); mkdir($stage, 0700);
                if (!$zip->extractTo($stage)) throw new RuntimeException('catalog_extract_failed');
                chmod($stage, 0755);
                if (!rename($stage, $target)) throw new RuntimeException('catalog_move_failed');
            } finally { $zip->close(); @unlink($zipPath); }
        }
        file_put_contents($receipt, json_encode(['status'=>'applying','sha256'=>$p['sha256']]), LOCK_EX);
        // Failed migrations are never replayed automatically; retain files/receipt for reconciliation.
        $this->command(['cms:plugin:activate', $name]);
        if (is_dir($target.'/database/migrations')) $this->command(['migrate','--force','--path=platform/plugins/'.$name.'/database/migrations']);
        $this->command(['cms:publish:assets']);
        $this->command(['cache:clear']);
        if (!in_array($name, $this->active(), true)) throw new RuntimeException('catalog_verification_failed');
        file_put_contents($receipt, json_encode(['status'=>'installed','sha256'=>$p['sha256'],'version'=>$p['version']]), LOCK_EX);
    }
}
