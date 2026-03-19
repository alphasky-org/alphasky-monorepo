<?php

namespace Alphasky\Theme\Facades;

use Alphasky\Theme\Manager as ManagerSupport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerTheme(array|string $theme)
 * @method static array getAllThemes()
 * @method static array getThemes()
 * @method static array getThemePresets(string $theme)
 * @method static void clearCache()
 * @method static array refreshThemes()
 *
 * @see \Alphasky\Theme\Manager
 */
class Manager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ManagerSupport::class;
    }
}
