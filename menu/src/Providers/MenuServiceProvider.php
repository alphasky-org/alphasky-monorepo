<?php

namespace Alphasky\Menu\Providers;

use Alphasky\Base\Facades\DashboardMenu;
use Alphasky\Base\Supports\DashboardMenuItem;
use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\Menu\Models\Menu as MenuModel;
use Alphasky\Menu\Models\MenuLocation;
use Alphasky\Menu\Models\MenuNode;
use Alphasky\Menu\Repositories\Eloquent\MenuLocationRepository;
use Alphasky\Menu\Repositories\Eloquent\MenuNodeRepository;
use Alphasky\Menu\Repositories\Eloquent\MenuRepository;
use Alphasky\Menu\Repositories\Interfaces\MenuInterface;
use Alphasky\Menu\Repositories\Interfaces\MenuLocationInterface;
use Alphasky\Menu\Repositories\Interfaces\MenuNodeInterface;
use Alphasky\Theme\Events\RenderingAdminBar;
use Alphasky\Theme\Facades\AdminBar;

class MenuServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind(MenuInterface::class, function () {
            return new MenuRepository(new MenuModel());
        });

        $this->app->bind(MenuNodeInterface::class, function () {
            return new MenuNodeRepository(new MenuNode());
        });

        $this->app->bind(MenuLocationInterface::class, function () {
            return new MenuLocationRepository(new MenuLocation());
        });
    }

    public function boot(): void
    {
        $this
            ->setNamespace('packages/menu')
            ->loadAndPublishConfigurations(['permissions', 'general'])
            ->loadHelpers()
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadMigrations()
            ->publishAssets();

        DashboardMenu::default()->beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-core-menu')
                        ->parentId('cms-core-appearance')
                        ->priority(2)
                        ->name('packages/menu::menu.name')
                        ->icon('ti ti-tournament')
                        ->route('menus.index')
                        ->permissions('menus.index')
                );
        });

        $this->app['events']->listen(RenderingAdminBar::class, function (): void {
            AdminBar::registerLink(
                trans('packages/menu::menu.name'),
                route('menus.index'),
                'appearance',
                'menus.index'
            );
        });

        $this->app->register(EventServiceProvider::class);
        $this->app->register(CommandServiceProvider::class);
    }
}
