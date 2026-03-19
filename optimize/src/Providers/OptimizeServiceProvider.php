<?php

namespace Alphasky\Optimize\Providers;

use Alphasky\Base\Facades\PanelSectionManager;
use Alphasky\Base\PanelSections\PanelSectionItem;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\Optimize\Facades\OptimizerHelper;
use Alphasky\Optimize\Http\Middleware\CollapseWhitespace;
use Alphasky\Optimize\Http\Middleware\DeferJavascript;
use Alphasky\Optimize\Http\Middleware\ElideAttributes;
use Alphasky\Optimize\Http\Middleware\InlineCss;
use Alphasky\Optimize\Http\Middleware\InsertDNSPrefetch;
use Alphasky\Optimize\Http\Middleware\RemoveComments;
use Alphasky\Optimize\Http\Middleware\RemoveQuotes;
use Alphasky\Setting\PanelSections\SettingCommonPanelSection;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Router;

class OptimizeServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this
            ->setNamespace('packages/optimize')
            ->loadAndPublishConfigurations(['general', 'permissions'])
            ->loadRoutes()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews();

        if (! class_exists('OptimizerHelper')) {
            AliasLoader::getInstance()->alias('OptimizerHelper', OptimizerHelper::class);
        }

        PanelSectionManager::default()->beforeRendering(function (): void {
            PanelSectionManager::registerItem(
                SettingCommonPanelSection::class,
                fn () => PanelSectionItem::make('common')
                    ->setTitle(trans('packages/optimize::optimize.settings.title'))
                    ->withIcon('ti ti-brand-speedtest')
                    ->withPriority(140)
                    ->withDescription(trans('packages/optimize::optimize.settings.description'))
                    ->withRoute('optimize.settings')
            );
        });

        $this->app['events']->listen(RouteMatched::class, function (): void {
            if (OptimizerHelper::isEnabled()) {
                /**
                 * @var Router $router
                 */
                $router = $this->app['router'];

                if (setting('optimize_elide_attributes', 0)) {
                    $router->pushMiddlewareToGroup('web', ElideAttributes::class);
                }

                if (setting('optimize_inline_css', 0)) {
                    $router->pushMiddlewareToGroup('web', InlineCss::class);
                }

                if (setting('optimize_insert_dns_prefetch', 0)) {
                    $router->pushMiddlewareToGroup('web', InsertDNSPrefetch::class);
                }

                if (setting('optimize_collapse_white_space', 0)) {
                    $router->pushMiddlewareToGroup('web', CollapseWhitespace::class);
                }

                if (setting('optimize_remove_comments', 0)) {
                    $router->pushMiddlewareToGroup('web', RemoveComments::class);
                }

                if (setting('optimize_remove_quotes', 0)) {
                    $router->pushMiddlewareToGroup('web', RemoveQuotes::class);
                }

                if (setting('optimize_defer_javascript', 0)) {
                    $router->pushMiddlewareToGroup('web', DeferJavascript::class);
                }
            }
        });
    }
}
