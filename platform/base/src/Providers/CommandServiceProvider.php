<?php
namespace Alphasky\Base\Providers;

use Alphasky\Base\Commands\CacheWarmCommand;
use Alphasky\Base\Commands\CleanupSystemCommand;
use Alphasky\Base\Commands\ClearExpiredCacheCommand;
use Alphasky\Base\Commands\ClearLogCommand;
use Alphasky\Base\Commands\CompressImagesCommand;
use Alphasky\Base\Commands\ExportDatabaseCommand;
use Alphasky\Base\Commands\FetchGoogleFontsCommand;
use Alphasky\Base\Commands\GoogleFontsUpdateCommand;
use Alphasky\Base\Commands\ImportDatabaseCommand;
use Alphasky\Base\Commands\InstallCommand;
use Alphasky\Base\Commands\PublishAssetsCommand;
use Alphasky\Base\Supports\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\AboutCommand;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CacheWarmCommand::class,
            CleanupSystemCommand::class,
            ClearExpiredCacheCommand::class,
            ClearLogCommand::class,
            ExportDatabaseCommand::class,
            FetchGoogleFontsCommand::class,
            ImportDatabaseCommand::class,
            InstallCommand::class,
            PublishAssetsCommand::class,
            GoogleFontsUpdateCommand::class,
            CompressImagesCommand::class,
        ]);

        AboutCommand::add('Core Information', fn() => [
            'CMS Version'  => get_cms_version(),
            'Core Version' => get_core_version(),
        ]);

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(ClearExpiredCacheCommand::class)->everyFiveMinutes();
        });
    }
}
