<?php

namespace Alphasky\Widget\Facades;

use Alphasky\Widget\AbstractWidget;
use Alphasky\Widget\WidgetGroup;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Alphasky\Widget\Factories\WidgetFactory registerWidget(string $widget)
 * @method static array getWidgets()
 * @method static \Illuminate\Support\HtmlString|string|null run()
 * @method static void ignoreCaches(array $widgets)
 *
 * @see \Alphasky\Widget\Factories\WidgetFactory
 */
class Widget extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'alphasky.widget';
    }

    public static function group(string $name): WidgetGroup
    {
        return app('alphasky.widget-group-collection')->group($name);
    }

    public static function ignoreCaches(array $widgets): void
    {
        AbstractWidget::ignoreCaches($widgets);
    }
}
