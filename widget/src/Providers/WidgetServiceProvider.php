<?php

namespace Alphasky\Widget\Providers;

use Alphasky\Base\Facades\DashboardMenu;
use Alphasky\Base\Supports\DashboardMenuItem;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\Theme\Events\RenderingAdminBar;
use Alphasky\Theme\Facades\AdminBar;
use Alphasky\Widget\Facades\WidgetGroup;
use Alphasky\Widget\Factories\WidgetFactory;
use Alphasky\Widget\Models\Widget;
use Alphasky\Widget\Repositories\Eloquent\WidgetRepository;
use Alphasky\Widget\Repositories\Interfaces\WidgetInterface;
use Alphasky\Widget\WidgetGroupCollection;
use Alphasky\Widget\Widgets\CoreSimpleMenu;
use Alphasky\Widget\Widgets\Text;
use Illuminate\Contracts\Foundation\Application;

class WidgetServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind(WidgetInterface::class, function () {
            return new WidgetRepository(new Widget());
        });

        $this->app->bind('alphasky.widget', function (Application $app) {
            return new WidgetFactory($app);
        });

        $this->app->singleton('alphasky.widget-group-collection', function (Application $app) {
            return new WidgetGroupCollection($app);
        });
    }

    public function boot(): void
    {
        $this
            ->setNamespace('packages/widget')
            ->loadAndPublishConfigurations(['permissions'])
            ->loadHelpers()
            ->loadRoutes()
            ->loadMigrations()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->publishAssets();

        $this->app->booted(function (): void {
            WidgetGroup::setGroup([
                'id' => 'primary_sidebar',
                'name' => trans('packages/widget::widget.primary_sidebar_name'),
                'description' => trans('packages/widget::widget.primary_sidebar_description'),
            ]);

            register_widget(CoreSimpleMenu::class);
            register_widget(Text::class);
        });

        DashboardMenu::default()->beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-core-widget')
                        ->parentId('cms-core-appearance')
                        ->priority(3)
                        ->name('packages/widget::widget.name')
                        ->icon('ti ti-layout')
                        ->route('widgets.index')
                        ->permissions('widgets.index')
                );
        });

        $this->app['events']->listen(RenderingAdminBar::class, function (): void {
            AdminBar::registerLink(
                trans('packages/widget::widget.name'),
                route('widgets.index'),
                'appearance',
                'widgets.index'
            );
        });

        $this->app->register(HookServiceProvider::class);
    }
}
