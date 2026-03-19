<?php

namespace Alphasky\Base\Facades;

use Alphasky\Base\Supports\DashboardMenu as DashboardMenuSupport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static static make()
 * @method static static setGroupId(string $id)
 * @method static static for(string $id)
 * @method static static default()
 * @method static static group(string $id, \Closure $callback)
 * @method static string getGroupId()
 * @method static static registerItem(\Illuminate\Contracts\Support\Arrayable|array $options)
 * @method static static removeItem(array|string $id)
 * @method static bool hasItem(string $id)
 * @method static \Illuminate\Support\Collection getAll(string|null $id = null)
 * @method static array|null getItemById(string $itemId)
 * @method static \Illuminate\Support\Collection|null getItemsByParentId(string $parentId)
 * @method static static beforeRetrieving(\Closure $callback)
 * @method static static afterRetrieved(\Closure $callback)
 * @method static void clearCachesForCurrentUser()
 * @method static void clearCaches()
 * @method static bool hasCache()
 * @method static \Alphasky\Base\Supports\DashboardMenu|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Alphasky\Base\Supports\DashboardMenu|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Alphasky\Base\Supports\DashboardMenu|\Illuminate\Support\HigherOrderTapProxy tap(callable|null $callback = null)
 *
 * @see \Alphasky\Base\Supports\DashboardMenu
 */
class DashboardMenu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DashboardMenuSupport::class;
    }
}
