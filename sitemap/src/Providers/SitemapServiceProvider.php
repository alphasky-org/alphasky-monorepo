<?php

namespace Alphasky\Sitemap\Providers;

use Alphasky\Base\Events\CreatedContentEvent;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Events\UpdatedContentEvent;
use Alphasky\Base\Facades\PanelSectionManager;
use Alphasky\Base\PanelSections\PanelSectionItem;
use Alphasky\Base\Services\ClearCacheService;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\Setting\PanelSections\SettingCommonPanelSection;
use Alphasky\Sitemap\Commands\IndexNowSubmissionCommand;
use Alphasky\Sitemap\Events\SitemapUpdatedEvent;
use Alphasky\Sitemap\Listeners\IndexNowSubmissionListener;
use Alphasky\Sitemap\Services\IndexNowService;
use Alphasky\Sitemap\Sitemap;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;

class SitemapServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    protected bool $defer = true;

    public function register(): void
    {
        $this->app->bind('sitemap', function (Application $app) {
            $config = $app['config']->get('packages.sitemap.config', []);

            return new Sitemap(
                $config,
                $app[Repository::class],
                $app['config'],
                $app['files'],
                $app[ResponseFactory::class],
                $app['view']
            );
        });

        $this->app->alias('sitemap', Sitemap::class);

        $this->app->singleton(IndexNowService::class);
    }

    public function boot(): void
    {
        $this
            ->setNamespace('packages/sitemap')
            ->loadAndPublishConfigurations(['config', 'permissions'])
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadRoutes()
            ->publishAssets();

        $this->app['events']->listen([
            CreatedContentEvent::class,
            UpdatedContentEvent::class,
            DeletedContentEvent::class,
        ], function (): void {
            ClearCacheService::make()->clearFrameworkCache();

            // Fire sitemap updated event to trigger search engine pings
            event(new SitemapUpdatedEvent());
        });

        $this->app['events']->listen(SitemapUpdatedEvent::class, IndexNowSubmissionListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                IndexNowSubmissionCommand::class,
            ]);
        }

        PanelSectionManager::default()->beforeRendering(function (): void {
            PanelSectionManager::registerItem(
                SettingCommonPanelSection::class,
                function () {
                    return PanelSectionItem::make('sitemap')
                        ->setTitle(trans('packages/sitemap::sitemap.settings.title'))
                        ->withIcon('ti ti-sitemap')
                        ->withDescription(trans('packages/sitemap::sitemap.settings.description'))
                        ->withPriority(1000)
                        ->withRoute('sitemap.settings');
                }
            );
        });
    }

    public function provides(): array
    {
        return ['sitemap', Sitemap::class];
    }
}
