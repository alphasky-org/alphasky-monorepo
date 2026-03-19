<?php

namespace Alphasky\SeoHelper\Providers;

use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\SeoHelper\Contracts\SeoHelperContract;
use Alphasky\SeoHelper\Contracts\SeoMetaContract;
use Alphasky\SeoHelper\Contracts\SeoOpenGraphContract;
use Alphasky\SeoHelper\Contracts\SeoTwitterContract;
use Alphasky\SeoHelper\SeoHelper;
use Alphasky\SeoHelper\SeoMeta;
use Alphasky\SeoHelper\SeoOpenGraph;
use Alphasky\SeoHelper\SeoTwitter;

/**
 * @since 02/12/2015 14:09 PM
 */
class SeoHelperServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind(SeoMetaContract::class, SeoMeta::class);
        $this->app->bind(SeoHelperContract::class, SeoHelper::class);
        $this->app->bind(SeoOpenGraphContract::class, SeoOpenGraph::class);
        $this->app->bind(SeoTwitterContract::class, SeoTwitter::class);
    }

    public function boot(): void
    {
        $this
            ->setNamespace('packages/seo-helper')
            ->loadHelpers()
            ->loadAndPublishConfigurations(['general'])
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->publishAssets();

        $this->app->register(EventServiceProvider::class);
        $this->app->register(HookServiceProvider::class);
    }
}
