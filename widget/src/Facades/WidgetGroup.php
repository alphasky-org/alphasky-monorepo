<?php

namespace Alphasky\Widget\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Alphasky\Widget\WidgetGroup group(string $sidebarId)
 * @method static \Alphasky\Widget\WidgetGroupCollection setGroup(array $args)
 * @method static \Alphasky\Widget\WidgetGroupCollection removeGroup(string $groupId)
 * @method static array getGroups()
 * @method static string render(string $sidebarId)
 * @method static void load(bool $force = false)
 * @method static \Illuminate\Support\Collection getData()
 *
 * @see \Alphasky\Widget\WidgetGroupCollection
 */
class WidgetGroup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'alphasky.widget-group-collection';
    }
}
