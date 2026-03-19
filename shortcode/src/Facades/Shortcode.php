<?php

namespace Alphasky\Shortcode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Alphasky\Shortcode\Shortcode register(string $key, string|null $name, string|null $description = null, $callback = null, string $previewImage = '')
 * @method static void remove(string $key)
 * @method static \Alphasky\Shortcode\Shortcode setPreviewImage(string $key, string $previewImage)
 * @method static \Alphasky\Shortcode\Shortcode enable()
 * @method static \Alphasky\Shortcode\Shortcode disable()
 * @method static \Illuminate\Support\HtmlString compile(string $value, bool $force = false)
 * @method static string|null strip(string|null $value)
 * @method static array getAll()
 * @method static void setAdminConfig(string $key, callable|\Closure|array|string|null $html)
 * @method static void modifyAdminConfig(string $key, callable|\Closure $callback)
 * @method static void registerLoadingState(string $shortcodeName, string $view)
 * @method static void ignoreCaches(array $shortcodes)
 * @method static void ignoreLazyLoading(array $shortcodes)
 * @method static string generateShortcode(string $name, array $attributes = [], string|null $content = null, bool $lazy = false)
 * @method static \Alphasky\Shortcode\Compilers\ShortcodeCompiler getCompiler()
 * @method static \Alphasky\Shortcode\ShortcodeField fields()
 *
 * @see \Alphasky\Shortcode\Shortcode
 */
class Shortcode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shortcode';
    }
}
