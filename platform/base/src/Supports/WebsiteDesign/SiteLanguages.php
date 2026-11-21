<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Alphasky\Language\Facades\Language;

final class SiteLanguages
{
    public static function inventory(): array
    {
        if (!is_plugin_active('language')) {
            $code = app()->getLocale();
            return ['default' => $code, 'enabled' => [$code]];
        }

        $enabled = array_values(array_unique(array_column(Language::getSupportedLocales(), 'lang_code')));
        $default = Language::getDefaultLocaleCode();
        if (!$default || !in_array($default, $enabled, true)) {
            throw new \RuntimeException('site_languages_unavailable');
        }
        return ['default' => $default, 'enabled' => $enabled];
    }
}
