<?php

namespace Alphasky\Theme\Facades;

use Alphasky\Theme\Supports\AdminBar as AdminBarSupport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isDisplay()
 * @method static \Alphasky\Theme\Supports\AdminBar setIsDisplay(bool $isDisplay = true)
 * @method static array getGroups()
 * @method static array getLinksNoGroup()
 * @method static \Alphasky\Theme\Supports\AdminBar setLinksNoGroup(array $links)
 * @method static \Alphasky\Theme\Supports\AdminBar registerGroup(string $slug, string $title, string $link = 'javascript:;')
 * @method static \Alphasky\Theme\Supports\AdminBar registerLink(string $title, string $url, $group = null, string|null $permission = null)
 * @method static string render()
 *
 * @see \Alphasky\Theme\Supports\AdminBar
 */
class AdminBar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AdminBarSupport::class;
    }
}
