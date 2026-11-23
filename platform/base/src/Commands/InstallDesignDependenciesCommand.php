<?php

namespace Alphasky\Base\Commands;

use Alphasky\Base\Supports\WebsiteDesign\DesignCatalogInstaller;
use Illuminate\Console\Command;
use RuntimeException;

final class InstallDesignDependenciesCommand extends Command
{
    protected $signature = 'cms:design:install-dependencies {module}';
    protected $description = 'Install verified catalog dependencies supplied with a generated page package';

    public function handle(DesignCatalogInstaller $installer): int
    {
        $module = $this->argument('module');
        if (!preg_match('/^design_[a-f0-9]{12}_pages_[a-f0-9]{10}$/D',$module)) throw new RuntimeException('Invalid content package');
        $dir = plugin_path($module.'/dependencies');
        $packages = json_decode(file_get_contents($dir.'/catalog.json'),true,flags:JSON_THROW_ON_ERROR);
        $installer->install($packages,static fn(string $path)=>file_get_contents($dir.'/'.basename($path).'.zip'));
        return self::SUCCESS;
    }
}
